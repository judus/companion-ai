<?php

namespace App\Collections;

use App\Models\Character;
use App\Services\Message;
use App\Services\MessageFormatter;

class MessageCollection extends TypedCollection
{
    protected static $allowedTypes = [Message::class];

    public function toString()
    {
        return $this->each(function (Message $message) {
            return (string)$message;
        });
    }

    public function toHistory()
    {
        return $this->each(function (Message $message) {
            return [
                'role' => $message->getRole(),
                'content' => $message->getContent(),
            ];
        });
    }

    public function toConversationString(Character $character)
    {
        return $this->each(function (Message $message) use ($character) {
            $from = $message->getRole() === 'assistant' ? $character->name : $message->getRole();
            return "{$from}: {$message->getContent()}";
        });
    }
}
