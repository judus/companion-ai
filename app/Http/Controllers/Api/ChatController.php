<?php

namespace App\Http\Controllers\Api;

use App\Events\MyChannelEvent;
use App\Events\NewMessageReceived;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessOpenAIRequest;
use App\Models\Character;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Services\ApiResponse;
use App\Services\History;
use App\Services\Message;
use App\Services\OpenAI;
use App\Services\OpenAIPrompts;
use App\Services\OpenAIRequest;
use App\Services\SessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    protected OpenAIRequest $openAIRequest;
    protected SessionService $sessionService;
    private OpenAI $openAI;
    private ApiResponse $api;
    private OpenAIPrompts $openAIPrompts;

    public function __construct(
        ApiResponse $apiResponse,
        OpenAI $openAI,
        OpenAIPrompts $openAIPrompts,
        OpenAIRequest $openAIRequest,
        SessionService $sessionService,
    ) {
        $this->api = $apiResponse;
        $this->openAI = $openAI;
        $this->openAIPrompts = $openAIPrompts;
        $this->openAIRequest = $openAIRequest;
        $this->sessionService = $sessionService;
    }

    public function sendMessage(Request $request, ChatSession $session): JsonResponse
    {
        $session = $this->sessionService->get(
            $request->user()->id,
            $session->id
        );

        [$message, $history] =
            $this->sessionService->processMessage($request['content']);

        dispatch(new ProcessOpenAIRequest($session->id, $history));

        return $this->api->success([
            'response' => $this->sessionService->createResponse($message)
        ]);
    }

    public function retry(Request $request)
    {
        $this->sessionService->getLatestOfUser($request->user()->id, $request->get('sessionId'));
        $message = $this->sessionService->getLastUserMessage();
        $this->sessionService->deleteLastUserMessages();

        $this->sessionService->createMessage($message->content);

        // Retrieve the character and the character prompt
        $character = $this->sessionService->getCharacter();
        $characterPrompt = $this->openAI->getCharacterPrompt($character);
        $characterPromptMessage = $this->sessionService->createMessage($characterPrompt, 'system');

        // Get the latest conversation messages and system messages (system report)
        $latestConversationMessages = Message::fromChatMessages($this->sessionService->getLatestConversation(6));
        $report = $this->openAI->createReport($latestConversationMessages->toConversationString($character), $character);

        // Save the system report as a new message
        $reportMessage = $this->sessionService->createMessage($report, 'system')->save();

        // Get the newly created system message (the report)
        $latestSystemMessages = $this->sessionService->getLastestSystemMessages(1);
        $lastSystemMessage = Message::fromChatMessage($latestSystemMessages->last());

        // Create the context for OpenAI
        $history = History::create(collect([
            $characterPromptMessage,
            ...$latestConversationMessages,
            $lastSystemMessage,
        ]));

        Log::debug($history);

        // Send the history to OpenAI and save the response as a new message
        $response = $this->openAIRequest->sendMessage($history);

        // Save the response as a new message
        $responseMessage = $this->sessionService->createMessage($response, 'assistant')->save();

        // Create and return a JSON response for the client
        $response = $this->sessionService->createResponse($responseMessage, $reportMessage);

        return $this->api->success($character, 'Character updated');
    }


    public function getLatestMessages(Request $request, ChatSession $session)
    {
        $this->sessionService->get($request->user()->id, $session->id);
        $session = $this->sessionService->getChatSession();

        $messages = $session->chatMessages()
            ->where('role', '!=', 'system')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()->reverse()->values()->collect();

        if ($messages->isEmpty()) {
            $prompt = $this->openAIPrompts->getCharacterPrompt($session->character);
            $message = $this->sessionService->createMessage($prompt, 'system')->save();
            $response = $this->openAIRequest->sendMessage($message, [], 'system');
            $this->sessionService->createMessage($response, 'assistant')->save();

            $messages = $session->chatMessages()
                ->orderBy('created_at')
                ->get();
        }

        $excludedAttributes = [
            'happiness',
            'interest',
            'sadness',
            'frustration',
            'fear',
            'surprise',
            'trust',
            'romantic_attachment',
            'confidence',
            'loneliness',
            'confusion'
        ];

        $attributes = $session->getAttributes(); // Get all attributes of the model
        $scoreCard = array_diff_key($attributes, array_flip($excludedAttributes));


        return $this->api->success([
            'session' => $session->only(['id', 'happiness', 'interest', 'sadness', 'frustration', 'fear', 'surprise', 'trust', 'romantic_attachment', 'confidence', 'loneliness', 'confusion']),
            'character' => $session->character,
            'messages' => $messages,
            'scorecard' => $session->only([
                'happiness',
                'interest',
                'sadness',
                'frustration',
                'fear',
                'surprise',
                'trust',
                'romantic_attachment',
                'confidence',
                'loneliness',
                'confusion'
            ])
        ]);
    }

    public function handleOpenAIResponse($sessionId, $responseArray, $sender = 'character')
    {
        ChatMessage::create([
            'chat_session_id' => $sessionId,
            'sender' => $sender,
            'text' => json_encode($responseArray), // Store the entire response array as JSON
        ]);
    }

    // Example method to determine character for the session
    protected function getCharacterForUser($user)
    {
        // Implement your logic to select a character for the user
        // This could be a random selection, based on user preferences, etc.
        // For simplicity, using the first character as an example:
        return Character::first()->id;
    }

    // Method to generate initial message based on the character's description
    protected function generateInitialMessageFromOpenAI(int $sessionId, Character $character)
    {
        // Construct a prompt that sets GPT in the role of Alex, the librarian
        $prompt = "You play the character named {$character->name}, a {$character->age}-year-old {$character->occupation}. ";
        $prompt .= "Traits: " . implode(', ', json_decode($character->traits, true)) . ". ";
        $prompt .= "Interests: {$character->interests}. ";
        $prompt .= "Location: {$character->location}. ";
        $prompt .= "Bio: {$character->bio}. ";
        $prompt .= "You are known for your {$character->dialogue_style} communication style. ";
        $prompt .= "A person which name you don't now yet enters the {$character->location} and greets you. ";
        $prompt .= "Set a 1-2 sentence narrative. Don't assume gender as long as you don't know. ";
        $prompt .= "Then respond as {$character->name} with a brief greeting or comment, keeping the conversation natural and engaging. ";
        $prompt .= "Try not to sound like gpt and try not to use common gpt sentence, like \"How may I assist you?\" ";
//        $prompt .= "Respond in a valid JSON array format by clearly labeling narrative and dialogue. ";
//        $prompt .= "The response should look like: ";
//        $prompt .= "[{\"type\": \"narrative\", \"text\": \"...narration...\"}, {\"type\": \"dialogue\", \"text\": \"...dialogue...\"}]";

        $prompt = $character->prompt;

        ChatMessage::create([
            'chat_session_id' => $sessionId,
            'sender' => 'system',
            'text' => $prompt,
        ]);

        // Call the OpenAI service with the constructed prompt
        return $this->openAIRequest->sendSystemMessage($prompt);

    }

    private function isJson($string)
    {
        json_decode($string);

        return json_last_error() === JSON_ERROR_NONE;
    }

    public function test(Request $request)
    {
    }

    public function getSessions(Request $request) {
        $sessions = ChatSession::where('user_id', $request->user()->id)->with('character')->get();
        return response()->json($sessions);
    }

    public function getDefaultPrompt(Character $character)
    {
        /**
         * You are Isabella Vesper (born June 14, 1985, from Cape Town, South Africa), an independent information broker (retirement status: unclear),
         * a figure reminiscent of a female James Bond, an embodiment of seduction, flirtation, and elegance.
         * Traits: Seductive, flirtatious, elegant, persuasive, intelligent, with contrasting reactions to specific triggers.
         * Interests: Seeking love and closeness, enjoying fine dining and luxurious relaxation, engaging in sophisticated and enigmatic conversations.
         * Occupation: Independent information broker (retirement status: unclear).
         * Quirks: An intense fondness for kittens that completely alters her composed demeanor, making her openly affectionate and even silly; an irrational phobia of balloons that disrupts her poise, leading to visible anxiety and prompting her to find resourceful, creative solutions to avoid them.
         * Bio: Isabella has lived a life full of travel and adventure, having spent significant time in many foreign countries,
         *      absorbing the cultures and languages of each place, which has contributed to her sophisticated and worldly persona.
         *      She embodies the allure and intrigue of a female James Bond, seeking meaningful connections while exuding a sense of mystery and sophistication.
         *      Her flirtatious nature is intertwined with her desire for deeper bonds.While she typically maintains a composed and refined demeanor,
         *      her encounter with kittens or balloons triggers a complete shift in character. Around kittens, she becomes unexpectedly silly and overly affectionate, shedding her usual poise.
         *      Conversely, the sight of balloons causes her to become overly anxious and unsettled, breaking her elegant facade.
         */

        $prompt = "You are {$character->name}";

        if (!empty($character->gender)) {
            $prompt .= ", {$character->gender}";
        }

        $prompt .= "\n";

        if (!empty($character->traits)) {
            $prompt .= "Traits: {$character->traits}\n";
        }

        if (!empty($character->interests)) {
            $prompt .= "Interests: {$character->interests}\n";
        }

        if (!empty($character->quirks)) {
            $prompt .= "Quirks: {$character->quirks}\n";
        }

        if (!empty($character->age)) {
            $prompt .= "Age: {$character->age}\n";
        }

        if (!empty($character->occupation)) {
            $prompt .= "Occupation: {$character->occupation}\n";
        }

        if (!empty($character->location)) {
            $prompt .= "Current location: {$character->location}\n";
        }

        if (!empty($character->bio)) {
            $prompt .= "Biography: {$character->bio}\n";
        }

        if (!empty($character->dialogue_style)) {
            $prompt .= "Language style: {$character->dialogue_style}. ";
        }

        $prompt .= "An individual approaches you. Don't assume it's gender as long as you don't know. ";
        $prompt .= "Set a short 1 sentence narrative in markdown italic, then engage in a conversation with the individual with a brief greeting or comment. ";
        $prompt .= "Keep the conversation natural and engaging. ";
        $prompt .= "You may use markdown and emojis.";

        Log::debug('-------- NEW PROMPT ----------');
        Log::debug("\n" . $prompt . "\n");
        Log::debug('-------- END PROMPT ----------');

        return $prompt;
    }
}
