<?php

namespace App\Services;

use App\Models\Character;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class SessionService
{
    protected ?ChatSession $chatSession;
    private OpenAI $openAI;

    public function __construct(
        OpenAI $openAI,
        ChatSession $chatSession = null
    ) {
        $this->openAI = $openAI;
        $this->chatSession = $chatSession;
    }

    /**
     * @param ChatSession|null $chatSession
     */
    public function setChatSession(?ChatSession $chatSession): void
    {
        $this->chatSession = $chatSession;
    }

    /**
     * @return ChatSession|null
     */
    public function getChatSession(): ?ChatSession
    {
        return $this->chatSession;
    }

    public function createNewSession(User $user, Character $character): ChatSession
    {
        // Create a new ChatSession instance with the character
        $session = new ChatSession();

        foreach (Emotions::ATTRIBUTES as $attribute) {
            $session->{$attribute} = $character->{$attribute};
        }

        $session->character()->associate($character);
        $session->user()->associate($user);
        $session->save();

        return $session;
    }

    public function updateScoreCard(array $attributes, ChatSession $session = null): SessionService
    {
        $session = $session ?? $this->chatSession;

        foreach ($attributes as $attribute => $value) {
            $session->{$attribute} = $value;
        }

        $session->save();

        return $this;
    }

    public function getMessageForOpenAI(ChatMessage $chatMessage): string
    {
        return MessageFormatter::fromJsonToString($chatMessage->text);
    }

    public function createMessage(string $content, string $role = 'user', ChatSession $session = null): Message
    {
        $session = $session ?? $this->chatSession;

        return (Message::make($content, $role))->setChatSession($session);
    }

    public function createResponse(Message $message, Message $report = null): array
    {
        $response = [$message->getChatMessage()];

        if ($report !== null) {
            array_unshift($response, $report->getChatMessage());
        }

        return $response;
    }


    public function getHistory(ChatSession $session = null)
    {
        $chatSession = $session ?? $this->chatSession;

        return $chatSession->chatMessages()->get()->map(function ($message) {
            return [
                "role" => $message->role,
                "content" => MessageFormatter::fromJsonToString($message->text),
            ];
        })->toArray();
    }

    public function get(int $userId, int $sessionId): ?ChatSession
    {
        $query = ChatSession::where('user_id', $userId);

        if ($sessionId) {
            $query->where('id', $sessionId);
        }

        $this->chatSession = $query->with([
            'character',
            'chatMessages' => function ($query) {
                $query->orderBy('created_at', 'asc'); //
            }
        ])->firstOrFail();

        return $this->chatSession;
    }

    public function getConversation(ChatSession $session = null): array
    {
        // Fetch the fresh chat session with messages if $session is not passed
        $chatSession = $session ?? ChatSession::with('chatMessages')->find($this->chatSession->id);
        $character = $chatSession->character()->first();

        // Order the messages by created_at in descending order and get the latest six non-system messages
        $latestMessages = $chatSession->chatMessages()
            ->where('role', '!=', 'system')
            ->orderBy('created_at', 'desc')
            ->take(6)
            ->get()
            ->reverse();

        // Get the last system message
        $lastSystemMessage = $chatSession->chatMessages()
            ->where('role', 'system')
            ->orderBy('created_at', 'desc')
            ->first();

        $latestMessages->prepend($lastSystemMessage);

        // Now build the conversation string
        $conversation = '';
        foreach ($latestMessages as $message) {
            $from = $message->role === 'assistant' ? $character->name : $message->role;
            $conversation .= "From {$from}: {$message->text}\n";
        }

        // Return the conversation string and the filtered messages collection
        return [$conversation, $latestMessages];
    }


    public function getCharacter(): Character
    {
        return $this->chatSession->character;
    }

    public function getLatestConversation(int $amount, ChatSession $chatSession = null): Collection
    {
        return ($chatSession ?? $this->chatSession)->chatMessages()
            ->where('role', '!=', 'system')
            ->latest()
            ->take($amount)
            ->get()
            ->reverse();
    }

    public function getLastestSystemMessages(int $amount, ChatSession $chatSession = null)
    {
        return ($chatSession ?? $this->chatSession)->chatMessages()
            ->where('role', 'system')
            ->latest()
            ->take($amount)
            ->get();
    }

    public function getLastUserMessage(ChatSession $chatSession = null)
    {
        return ($chatSession ?? $this->chatSession)->chatMessages()
            ->where('role', 'user')
            ->latest()
            ->first();
    }

    public function deleteLastUserMessages(ChatSession $chatSession = null)
    {
        $session = $chatSession ?? $this->chatSession;

        // Get the latest user message
        $lastUserMessage = $session->chatMessages()
            ->where('role', 'user')
            ->latest()
            ->first();

        if ($lastUserMessage) {
            // Delete the last user message and any subsequent messages
            $session->chatMessages()
                ->where('created_at', '>=', $lastUserMessage->created_at)
                ->delete();
        }
    }

    public function getScoreCard(ChatSession $session = null): array
    {
        $session = $session ?? $this->chatSession;

        return $session->only(Emotions::ATTRIBUTES);
    }

    public function processMessage(string $message): array
    {
        /** @var OpenAIPrompts $prompts */
        $prompts = app(OpenAIPrompts::class);

        // Create a new message from the request and save it to the DB
        $message = $this->createMessage($message)->save(); // save to DB

        // Retrieve the character prompt and include the scorecard values to
        // the system prompt
        $prompt = $prompts->getCharacterPrompt(
            $this->getCharacter(),
            $this->getScoreCard()
        );

        // Create a new system message ({ role: system, content: characterPrompt }})
        // for openai. We don't want to save it to the DB, only send it to openai
        $promptMessage = $this->createMessage($prompt, 'system');

        // Start creating the context for OpenAI
        $history = History::create(collect([$promptMessage]));

        // Retrieve the latest messages from the user's conversation with the character
        // and add them to the context
        $latestMessages = Message::fromChatMessages($this->getLatestConversation(11));
        foreach ($latestMessages as $latestMessage) {
            $history->push($latestMessage->toArray());
        }

        Log::debug("NEW CHAT MESSAGE: {$message}");
        Log::debug("NEW CHAT HISTORY: {$history}");

        return [$message, $history];
    }

    public function processResponse(string $response, ChatSession $session)
    {
        /** @var OpenAI $openAI */
        $openAI = app(OpenAI::class);

        // Extract the  updated scorecard values from the openai response
        // and save them to the session
        if ($scorecard = $openAI->extractScorecard($response)) {
            $this->updateScorecard($scorecard, $session);
        }

        // Clean the response from openai, e.g. remove the scorecard and other
        // unwanted text
        $response = $openAI->cleanChatResponse($response);


        return [$response, $scorecard];
    }


}
