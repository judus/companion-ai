<?php

namespace App\Services;

use App\Models\ChatSession;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class OpenAIRequest
{
    protected $client;
    protected $apiKey;

    public function __construct()
    {
        $this->apiKey = config('openai.api_key');
        $this->client = new Client([
            'base_uri' => 'https://api.openai.com',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'timeout' => 120.0,
        ]);
    }

    public function sendMessage(string $content, array $history = [], $role = 'user', $context = null)
    {
        $history[] = ["role" => $role, "content" => $content];

        if ($context) {
            $history[] = ["role" => "system", "content" => $context];
        }

        return $this->sendRequest($history);
    }

    public function sendHistory(array $history)
    {
        return $this->sendRequest($history);
    }

    public function sendSystemMessage(string $message)
    {
        return $this->sendMessage($message, [], 'system');
    }

    public function formatPromptForOpenAI($user, $character, $newMessage, $history)
    {
        $formattedHistory = [];
        foreach ($history as $message) {
            $decodedMessage = json_decode($message->text, true);
            if (is_array($decodedMessage)) {
                // For messages from the assistant, format narrative and dialogue
                $formattedContent = '';

                foreach ($decodedMessage as $part) {
                    if ($part['type'] === 'narrative') {
                        $formattedContent .= "Narrative: {$part['text']}\n";
                    } elseif ($part['type'] === 'dialogue') {
                        $formattedContent .= "Dialogue: {$part['text']}\n";
                    }
                }

                $formattedHistory[] = [
                    'role' => $message->sender,
                    'content' => $formattedContent
                ];
            } else {
                $formattedHistory[] = [
                    'role' => $message->sender,
                    'content' => $message->text
                ];
            }
        }

        $newMessageContent = json_decode($newMessage['text'], true);
        $formattedNewMessage = '';

        if (is_array($newMessageContent)) {
            foreach ($newMessageContent as $part) {
                if ($part['type'] === 'narrative') {
                    $formattedNewMessage .= "Narrative: {$part['text']}\n";
                } elseif ($part['type'] === 'dialogue') {
                    $formattedNewMessage .= "Dialogue: {$part['text']}\n";
                }
            }

        }

        // Return the formatted history as a JSON string
        return [$formattedHistory, $formattedNewMessage];
    }

    /**
     * @param array $history
     *
     * @return mixed|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function sendRequest(array $history): mixed
    {
        //Log::debug('Sending Request with History:', $history);

        try {
            $response = $this->client->post('/v1/chat/completions', [
                'json' => [
                    'model' => 'gpt-3.5-turbo',
                    'messages' => $history,
                    'temperature' => 0.7
                ],
            ]);
            //Log::debug('Response Status Code: ' . $response->getStatusCode());
            $body = json_decode((string)$response->getBody(), true);
            //Log::debug('Response Body:', $body);

            return $body['choices'][0]['message']['content'] ?? 'No response';
        } catch (\Exception $e) {
            Log::error('Request Exception: ', ['exception' => $e]);
            return 'Error: ' . $e->getMessage();
        }
    }

    public function getHistory(ChatSession $session)
    {
        return $session->chatMessages->map(function ($message) {
            return [
                "role" => $message->sender,
                "content" => MessageFormatter::fromJsonToString($message->text),
            ];
        })->toArray();
    }

}
