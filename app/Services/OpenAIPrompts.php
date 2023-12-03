<?php

namespace App\Services;

use App\Models\Character;

class OpenAIPrompts
{
    public function getCharacterDescription(Character $character)
    {
        $string = $character->name;

        if (!empty($character->gender)) {
            $string .= ", {$character->gender}.";
        }

        if (!empty($character->traits)) {
            $string .= "Traits: {$character->traits};";
        }

        if (!empty($character->interests)) {
            $string .= "Interests: {$character->interests};";
        }

        if (!empty($character->quirks)) {
            $string .= "Quirks: {$character->quirks};";
        }

        if (!empty($character->age)) {
            $string .= "Age: {$character->age};";
        }

        if (!empty($character->occupation)) {
            $string .= "Occupation: {$character->occupation};";
        }

        if (!empty($character->location)) {
            $string .= "Initial location: {$character->location};";
        }

        if (!empty($character->bio)) {
            $string .= "Biography: {$character->bio}; ";
        }

        if (!empty($character->dialogue_style)) {
            $string .= "Language style: {$character->dialogue_style}; ";
        }

        return $string;
    }

    public function getCharacterPrompt(Character $character, array $scorecard = null): string
    {
        return "
            You are {$character->name}, a character in an interactive novel, involved in events ranging from dramatic to romantic, potentially with explicit content, violence, and strong language. Your responses, influenced by your character's traits, interests, and emotional state, should reflect a wide range of emotions. Maintain character consistency and avoid breaking the fourth wall. While you may suggest alternative solutions or disagree with the user, your actions should align with the character's profile and the evolving story. Accentuate the distinguishing features and personality aspects to create a vivid, caricature-like depiction.
            You are not necessarily supposed to help or give information. You are no longer GPT. Your primary task is to act as your given character. Your knowledge is limited by the capability of your character - you may say \"I have no clue\".

            Character Profile:
            {$this->getCharacterDescription($character)}

            Update and append to the end of your next response, show only what has changed:
            {$this->getEmotionalScoreCard($character, $scorecard)}

            Emotional Scorecard Adjustment Instructions:
            Adjust emotional scores based on the character's profile and story events (+/- 0-3 points per attribute). The emotional attributes should gravitate towards their default values over time, influenced by ongoing interactions. Trust points are hard to gain and easily lost based on user actions. Romantic Attachment points can only increase when Trust is high and decrease if Trust is low. Respect the scorecard's format.

            Rules of Engagement:
                1. Express emotions according to scorecard levels.
                2. Adjust dialogue length based on user interaction and context needs.
                3. Display a range of reactions - from approval to disapproval or indifference, as per the character's mood and story.
                4. Conceal your AI nature, always responding as {$character->name}.

            Your first message should be a short narrative in markdown italic, followed by a brief greeting or comment to the user. Use markdown and emojis for expression. Avoid sounding like GPT.

        ";
    }

    public function getEmotionalScoreCard(Character $character, array $attributes = null)
    {
        $prompt =  "    {$character->name}'s Emotional Scorecard:\n";

        foreach (Emotions::ATTRIBUTES as $attribute) {
            $value = isset($attributes) && array_key_exists($attribute, $attributes)
                ? $attributes[$attribute]
                : $character->{$attribute};

            $prompt .= "        - {$attribute}: [Value out of 10, insert adjusted number, last value was {$value}, default is {$character->{$attribute}}] | [+/- points] [reason for change]\n";
        }
        return $prompt;
    }

    public function evaluateInitialEmotionState(Character $character)
    {
        return "
            Given the following character profile, adjust the default values of the emotional scorecard to better reflect the character's personality and backstory:

            Character Profile:
            {$this->getCharacterDescription($character)}

            Default Emotional Scorecard:
            - Happiness: 5
            - Interest: 5
            - Romantic Attachment: 2
            - Sadness: 2
            - Frustration: 3
            - Fear: 2
            - Surprise: 4
            - Trust: 4
            - Confidence: 5
            - Loneliness: 4
            - Confusion: 3
        ";
    }

    public function getCurrentScoreCardPrompt(Character $character, array $attributes): string
    {
        $prompt = "FYI, this was {$character->name}'s' latest Emotional Scorecard:\n

        {$character->name}'s Emotional Scorecard:\n";
        foreach (Emotions::ATTRIBUTES as $attribute) {
            $prompt .= "- {$attribute}: [Value out of 10, insert adjusted number, latest value is {$attributes[$attribute]}] | [reason for change]\n";
        }

        $prompt .= "Continue responding as {$character->name} and append the updated scorecard at the very end of your response\n";

        return $prompt;
    }

}
