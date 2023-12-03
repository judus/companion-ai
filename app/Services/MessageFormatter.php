<?php

namespace App\Services;

class MessageFormatter
{
    public static function fromJsonToArray(string $string): ?array
    {
        if (!$messageArray = json_decode($string, true)) {
            $messageArray = [['text' => $string]];
        }

        return $messageArray;
    }

    public static function fromJsonToString(string $string): string
    {
        return self::fromArrayToString(self::fromJsonToArray($string));
    }


    public static function fromStringToJson(string $string)
    {
        return json_encode([
            [
                'type' => 'dialogue',
                'text' => $string
            ]
        ]);
    }

    public static function fromArrayToString(array $array)
    {
        return collect($array)->map(function ($element) {
            return $element['text'];
        })->implode("\n\n");
    }

    public static function fromArrayToJson(array $array)
    {
        return json_encode($array);
    }
}
