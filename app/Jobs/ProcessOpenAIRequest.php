<?php

namespace App\Jobs;

use App\Events\NewMessageReceived;
use App\Models\ChatSession;
use App\Models\User;
use App\Services\History;
use App\Services\OpenAI;
use App\Services\OpenAIRequest;
use App\Services\SessionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOpenAIRequest implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private History $history;
    private int $sessionId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        $sessionId,
        History $history
    ) {
        $this->sessionId = $sessionId;
        $this->history = $history;
        Log::debug("NEW ProcessOpenAIRequest JOB");
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /** @var OpenAI $openAI */
        $openAI = app(OpenAI::class);
        /** @var OpenAIRequest $openAIRequest */
        $openAIRequest = app(OpenAIRequest::class);
        /** @var SessionService $sessionService */
        $sessionService = app(SessionService::class);

        $session = ChatSession::find($this->sessionId);

        $response = $openAIRequest->sendMessage($this->history);
        Log::debug("NEW OPENAI RESPONSE: $response");

        [$response, $scorecard] = $sessionService->processResponse($response, $session);

        Log::debug("UPDATED SCORECARD: " . collect($scorecard));
        Log::debug("NEW MESSAGE FOR USER AND DB: $response");

        // Save the cleaned up response as new message with role assistant
        $responseMessage = $sessionService->createMessage($response, 'assistant', $session)->save();

        broadcast(new NewMessageReceived($session->user_id, $session->id, [
            'response' => [$responseMessage->toArray()],
            'scorecard' => $scorecard ?? [],
        ]));
    }
}
