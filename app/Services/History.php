<?php

namespace App\Services;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Stringable;

class History implements Stringable, Arrayable
{
    private Collection $messages;

    public function __construct(Collection $messages)
    {
        $this->messages = collect();

        $messages->each(function(Message $message) {
            $this->addMessage($message->getRole(), $message->getContent());
        });
    }

    public static function create(Collection $messages): History
    {
        return new static($messages);
    }

    public function addMessage(string $role, string $content): History
    {
        $this->messages->push([
           'role' => $role,
           'content' => $content
        ]);

        return $this;
    }

    public function toArray(): array
    {
        return $this->messages->toArray();
    }

    public function __toString(): string
    {
        return $this->messages->map(function ($message) {
            return "{$message['role']}: {$message['content']}\n\n";
        })->join('');
    }

    public function __call(string $name, array $arguments)
    {
        return $this->messages->$name(...$arguments);
    }
}
