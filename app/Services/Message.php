<?php

namespace App\Services;

use App\Collections\MessageCollection;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class Message implements Arrayable
{
    protected ChatMessage $chatMessage;
    protected ChatSession $chatSession;
    protected ?int $id = null;
    protected ?int $session = null;
    protected string $role = '';
    protected string $content = '';

    public function __construct(string $content = null, string $role = 'user')
    {
        if ($content) {
            $this->id = null;
            $this->session = null;
            $this->role = $role;
            $this->content = $content;
        }
    }

    public static function make(string $content, string $role): static
    {
        return new static($content, $role);
    }

    public static function fromChatMessage(ChatMessage $chatMessage): static
    {
        return new static($chatMessage->content, $chatMessage->role);
    }

    public static function fromChatMessages(Collection $chatMessages): Collection
    {
        return MessageCollection::make($chatMessages->map(function (ChatMessage $chatMessage) {
            return static::fromChatMessage($chatMessage);
        }));
    }

    /**
     * @return ChatMessage
     */
    public function getChatMessage(): ChatMessage
    {
        return $this->chatMessage;
    }

    /**
     * @param ChatMessage $chatMessage
     *
     * @return Message
     */
    public function setChatMessage(ChatMessage $chatMessage): Message
    {
        $this->chatMessage = $chatMessage;

        return $this;
    }

    /**
     * @return ChatSession
     */
    public function getChatSession(): ChatSession
    {
        return $this->chatSession;
    }

    /**
     * @param ChatSession $chatSession
     *
     * @return Message
     */
    public function setChatSession(ChatSession $chatSession): Message
    {
        $this->chatSession = $chatSession;

        return $this;
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * @param string $role
     *
     * @return Message
     */
    public function setRole(string $role): Message
    {
        $this->role = $role;

        return $this;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @param string $content
     *
     * @return Message
     */
    public function setContent(string $content): Message
    {
        $this->content = $content;

        return $this;
    }

    public function save()
    {
        $this->chatMessage = ChatMessage::create([
            'chat_session_id' => $this->chatSession->id,
            'role' => $this->role,
            'content' => $this->content
        ]);

        $this->id = $this->chatMessage->id;

        return $this;
    }

    public function __toString()
    {
        return $this->content;
    }

    public function toArray()
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'content' => $this->content
        ];
    }
}
