<?php
namespace App\Services;

use Illuminate\Contracts\Support\Jsonable;

class FormattedMessage implements Jsonable {
    public function __construct(string $message)
    {
        $this->message = $this->parseToArray($message);
    }

    public function parseToArray(string $message)
    {
        return [
            [
                'type' => 'dialogue',
                'text' => $message
            ]
        ];
    }

    public function toJson($options = 0)
    {
        return json_encode($this->message, true);
    }

    public function __toString()
    {
        return json_encode($this->message, true);
    }

}
