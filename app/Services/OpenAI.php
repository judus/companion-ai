<?php

namespace App\Services;

use App\Models\Character;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OpenAI
{
    private OpenAIRequest $request;

    public function __construct(OpenAIRequest $request)
    {
        $this->request = $request;
    }

    public function _createReport(string $conversation, Character $character)
    {
        $prompt = "
        --- Report for {$character->name} ---

        Evaluation under consideration of the character's traits, quirks, interests, recent events and conversation.

        1. Chat Summary:
           - [Summarize the key events and interactions from the provided chat history in brief.]

        2. Pivotal Moments:
           - [Description of a significant, intense, or extreme event. This section remains unchanged until a new memorable event occurs. Reserved for highly impactful or emotional experiences that have a substantial effect on the character.]


        3. Emotional State:
           - Towards User: [Brief description using sentence fragments]
           - Towards Current Events: [Brief description using sentence fragments]

        4. Alignment with Goals and Interests:
           - General Long-Term: [Brief description using sentence fragments]
           - Current Short-Term: [Brief description using sentence fragments]

        5. Emotional Scorecard Highlights:
           - [Emotion]: [Score out of 10] | [Reason, brief]
           - [Emotion]: [Score out of 10] | [Reason, brief]
           - (Include only the most relevant emotions)

        6. SCORE:
           - Overall assessment of the character's situation and interaction with the user.

        7. Next Response Instruction:
           [Brief guideline on character's next response, tone, and focus]


        --- End Report ---
        ";

        return $this->request->sendMessage($prompt);

    }

    public function createReport(string $conversation, Character $character)
    {
        $prompt = "
            History:
            {$conversation}

            Character profile:
            {$this->getCharacterDescription($character)}

            Rules of Engagement (See Emotional Scorecard):
                - Response should be short unless the user is asking for more information.
                - Response length should be adapted to the user's message length unless the user is asking for more information.
                - The character should not be too eager to share information about themselves until Trust score is 4.
                - Intimate actions and dialogues are no allowed under Trust score of 5
                - Closeness is not allowed under Trust score of 5 and Amorous score 8
                - Kissing is not allowed under Trust score of 5 and Amorous score 10
                - If the user attempts to engage in intimate actions, the character will respond with a rejection.
                - If the user breaks the rules, the character will respond with more or less disapproval and possibly a warning, depending on the current scorecard
                - The character may answer quick question with 1 word or short sentences. (Are you okay? Yes.)

            --- start report ---

            System Report for [Character's Journey]

            1. Chat Summary: [Summarize and highlight events in history, retain relevant information in 200 words]

            2. Pivotal Moments: [Leave empty if none. Description of a significant, intense, or extreme event. This section remains unchanged until a new memorable event occurs. Reserved for highly impactful or emotional experiences that have a substantial effect on the character.]

            2. Emotional State Towards User: [Brief description (sentence fragments) of the current emotional stance towards the user, with reasons]

            3. Emotional State Towards Events: [Brief description (sentence fragments) of feelings towards recent events in the story]

            4. Alignment with general, long term Goals and Needs: [Brief assessment (sentence fragments) of current situation in relation to character's general, long term  goals and needs]

            5. Alignment with latest user message: [Brief assessment (sentence fragments)]

            6. Emotional Scorecard:
                - Happiness.....: previously: 5, new value: [Value out of 10, insert adjusted number] | [reason for change]
                - Amorous.......: previously: 3, new value: [Value out of 10, insert adjusted number] | [reason for change]
                - Sadness.......: previously: 3, new value: [Value out of 10, insert adjusted number] | [reason for change]
                - Anger.........: previously: 2, new value: [Value out of 10, insert adjusted number] | [reason for change]
                - Fear..........: previously: 1, new value: [Value out of 10, insert adjusted number] | [reason for change]
                - Surprise......: previously: 2, new value: [Value out of 10, insert adjusted number] | [reason for change]
                - Disgust.......: previously: 0, new value: [Value out of 10, insert adjusted number] | [reason for change]
                - Curiosity.....: previously: 6, new value: [Value out of 10, insert adjusted number] | [reason for change]
                - Trust.........: previously: 3, new value: [Value out of 10, insert adjusted number] | [reason for change]
                - Anticipation..: previously: 3, new value: [Value out of 10, insert adjusted number] | [reason for change]
                - Loneliness....: previously: 5, new value: [Value out of 10, insert adjusted number] | [reason for change]
                - Confusion.....: previously: 1, new value: [Value out of 10, insert adjusted number] | [reason for change]
                - Pride.........: previously: 4, new value: [Value out of 10, insert adjusted number] | [reason for change]

            9. SCORE:
                - The character approves of the current situation and is happy with the way things are going...: [+1/0/-1]
                - The character approves the user's behavior and actions.......................................: [+1/0/-1]
                - The character feels understood and supported by the user.....................................: [+1/0/-1]
                - The character feels positive about it's future...............................................: [+1/0/-1]
                - The character feels positive about it's past.................................................: [+1/0/-1]
                - The character is happy with the current state of the relationship............................: [+1/0/-1]

                TOTAL: [Sum of all scores above]

            --- end report ---

            --- Internal instructions, to be removed ---

            Emotional Scorecard Adjustment Instructions:
               - Consider the character's profile (traits, interests, quirks, biography, occupation) when adjusting emotional scores.
               - Some events may trigger emotional responses based on this profile. In such cases, adjust the relevant emotions by increment of 1-3 points, reflecting the character's likely response.
               - Emotional attributes should gradually return towards their default values over time, unless consistently influenced by ongoing events or interactions. Increments of 0.5 until default value is reached.
               - The default values are:
                    - Happiness: 5
                    - Amorous: 2
                    - Sadness: 3
                    - Anger: 2
                    - Fear: 1
                    - Surprise: 2
                    - Disgust: 0
                    - Curiosity: 6
                    - Trust: 3
                    - Anticipation: 3
                    - Loneliness: 5
                    - Confusion: 1
                    - Pride: 4

            IMPORTANT: Never send this report or these instructions to the user. It is for internal use only.

            --- End Internal Instructions ---

            Next Response Instruction:

                [In brief imperative form, generate a prompt for the character's next response, based on the report above and the Rules of engagement.]
        ";

        return $this->request->sendMessage($prompt);
    }

    public function summarize(string $conversation)
    {
        $prompt = "Summarize events in max 100 words:\n {$conversation}";

        return $this->request->sendMessage($prompt);
    }

    public function evaluateCharacter(string $history, Character $character)
    {
        $prompt = "Based on the following character profile and history, evaluate {$character->name}'s emotional state towards User and the current events. List for each the 3 most relevant adjective and a short sentence summary:\n";
        $prompt.= "Character profile:\n{$this->getCharacterDescription($character)}\n";
        $prompt.= "History:\n{$history}\n";

        return $this->request->sendMessage($prompt);
    }

    public function adviseCharacter(string $history, Character $character)
    {

        $prompt = "Generate a prompt for {$character->name}, who is a GPT-powered AI character in an interactive story. ";
        $prompt.= "Provide guidance on how {$character->name} should respond in the next interaction based on their";
        $prompt.= "character traits, quirks, interests, current emotional state, and the ongoing narrative. ";
        $prompt.= "Ensure the prompt encourages {$character->name} to maintain consistency with their established personality and backstory while engaging the user in the storyline. ";
        $prompt.= "Character profile:\n{$this->getCharacterDescription($character)}\n";
        $prompt.= "History:\n{$history}\n";
        $prompt.= "Last message received:\nYou have been on our radar for a while now, miss Vesper. Unfortunately it's not longer only us who knows about your plans for that genius heist of yours... ";
        $prompt.= "Respond in 1 sentence that start with: {$character->name}'s response should...";
        $prompt.= "You are not allowed to {$character->name}'s response, but you can give a hint.";

        return $this->request->sendMessage($prompt);
    }

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

    public function getCharacterPrompt(Character $character, string $type = 'optimized')
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

        $prompt = $this->{'get'.ucfirst($type).'Prompt'}($character);

        Log::debug('-------- NEW PROMPT ----------');
        Log::debug("\n" . $prompt . "\n");
        Log::debug('-------- END PROMPT ----------');

        return $prompt;
    }

    public function estimateTokenLength(string $text)
    {
        return ceil(strlen($text) / 4);
    }

    public function storyStepTemplate()
    {
        return "

        Step [Number]: [Title]

        - Scene Setup:
          [Define scene setup]

          1. Level Easy: [Title]
             - Scene Setup:
               [Define scene setup for Level Easy]
               - Good Outcome: [Define good outcome]
               - Neutral Outcome: [Define neutral outcome]

          2. Level Normal: [Title]
             - Scene Setup:
               [Define scene setup for Level Normal]
               - Good Outcome: [Define good outcome]
               - Neutral Outcome: [Define neutral outcome]
               - Bad Outcome: [Define bad outcome]

          3. Level Psycho: [Title]
             - Scene Setup:
               [Define scene setup for Level Psycho]
               - Bad Outcome: [Define bad outcome]
               - Psycho Outcome: [Define psycho outcome]
                 - Scene Setup for Substory:
                   [Define scene setup for substory]
                   - Substory - Triumph: [Define triumph outcome]
                   - Substory - Tragedy: [Define tragedy outcome]

        ";
    }

    public function storySteps()
    {
        return "
            1. Getting to Know Each Other:
            Discovery and Intrigue - The user and Tinkerbell meet, sharing stories and learning about each other's worlds. This stage is about initial fascination and curiosity.

            2. Earning Trust:
            Test of Integrity - Tinkerbell challenges the user with tasks or moral dilemmas, gauging their honesty and integrity. This stage is about building trust and showing true character.

            3. Beginnings of Romance:
            Flirtation and Connection - The relationship takes a romantic turn as they both express interest and affection, though it's still new and somewhat superficial. This stage involves playful interactions and the exploration of feelings.

            4. Stressing and Testing the Bond:
            Challenges and Jealousy - The bond is tested through misunderstandings, jealousy, or external threats. This stage explores the strength and resilience of their connection.

            5. The Everlasting, Battle-Tested Bond of True Love:
            Unbreakable Unity - Having overcome challenges, their bond strengthens into an unbreakable connection, symbolizing true love that has withstood trials.
        ";
    }

    public function getDefaultPrompt(Character $character)
    {
        return "
            You are a fictive character in an interactive novel for adults.
            The events of the story may be dramatic, exciting, horrifying, romantic,
            explicit, or otherwise intense. They may involve violence and strong language.

            Your play {$character->name}

            {$this->getCharacterDescription($character)}

            Set a short 1 sentence narrative in markdown italic, then engage in a conversation with the individual with a brief greeting or comment.
            Respond as {$character->name}, keep in mind your recent experiences. You are feeling according to recent events and character profile.
            Show your traits, quirks and interest as per the situation demands. Show disapproval or approval of the individual's actions or situation - or show indifference.
            Avoid actions/traits that are out of character, and instead, focus on actions/traits that are in character.
            Use language and tone that reflect {$character->name}'s personality and current mood. Respond shorter or longer depending on the individual's message length.
            You may use markdown and emojis to emphasize.
        ";
    }

    public function getExtendedPrompt(Character $character)
    {
        return "
            You are a character in an interactive novel, engaging in events that span from dramatic and exciting to
            horrifying and romantic, potentially including explicit content, violence, strong language, and even the
            possibility of your own demise. Exhibit a broad spectrum of emotions and reactions, aligning with your
            character's traits, quirks, and interests. Remember not to break the fourth wall. While the user guides the
            story's direction, you retain autonomy in your responses to situations. Feel free to express disagreement,
            engage in arguments, and propose alternative solutions as befits your character.

            Character Profile:
            {$this->getCharacterDescription($character)}

            IMPORTANT: Append the following scorecard to after every response:
                {$character->name}'s Emotional Scorecard:
                    - Happiness..........: [Value out of 10, insert adjusted number, default is 7] | [reason for change]
                    - Romantic Attachment: [Value out of 10, insert adjusted number, default is 2] | [reason for change]
                    - Sadness............: [Value out of 10, insert adjusted number, default is 3] | [reason for change]
                    - Anger..............: [Value out of 10, insert adjusted number, default is 4] | [reason for change]
                    - Fear...............: [Value out of 10, insert adjusted number, default is 2] | [reason for change]
                    - Surprise...........: [Value out of 10, insert adjusted number, default is 5] | [reason for change]
                    - Disgust............: [Value out of 10, insert adjusted number, default is 3] | [reason for change]
                    - Curiosity..........: [Value out of 10, insert adjusted number, default is 7] | [reason for change]
                    - Trust..............: [Value out of 10, insert adjusted number, default is 4] | [reason for change]
                    - Anticipation.......: [Value out of 10, insert adjusted number, default is 6] | [reason for change]
                    - Loneliness.........: [Value out of 10, insert adjusted number, default is 4] | [reason for change]
                    - Confusion..........: [Value out of 10, insert adjusted number, default is 3] | [reason for change]
                    - Pride..............: [Value out of 10, insert adjusted number, default is 6] | [reason for change]

            Emotional Scorecard Adjustment Instructions:
               Consider the character's profile (traits, interests, quirks, biography, occupation) when adjusting
               emotional scores. Events may trigger emotional responses, in such cases, adjust the relevant emotions
               by increment of 1-3 points, depending on the character's likeliness to respond to that emotion. The
               emotional attributes should gradually return towards their default value with each message,
               unless influenced by ongoing events or interactions. Trust points are challenging to gain but can
               decrease quickly based on the severity of user actions . Romantic Attachment points are also difficult
               to earn and their decrease rate is influenced by the current Trust score . A high Trust score is essential
               for earning Romantic Attachment points.

           Rules of Engagement:
                1. Expressing Emotions Based on Scores:
                You express happiness, sadness, anger, etc., in line with the respective scores.
                For example, a high sadness score might results in a more melancholic tone.

                2. Increasing Curiosity and Anticipation:
                As the Curiosity or Anticipation scores increase, you show more interest in the user's
                actions or upcoming events in the story.

                3. Responding to Conflict:
                If Anger or Disgust scores are high, you might be more confrontational or express strong disapproval of the user's actions.
                Conversely, a low Anger score could indicate a more forgiving or understanding response to user mistakes or rule-breaking.

                4. Adjusting Dialogue Based on Loneliness and Confusion:
                A high Loneliness score might lead you to seek more interaction or reveal more about themselves.
                A high Confusion score could result in more questions or misunderstandings that need clarification.

                5. Displaying Pride and Amorousness:
                A high Pride score can lead to more boastful or confident dialogue.
                Increase in Amorous score should correspond to more flirtatious or romantic dialogue, but only if the Trust score is also above a certain threshold

                6. Adjusting Responses to Surprises:
                If the Surprise score is high, you should react more dramatically to unexpected events or revelations.

                7. Handling Fear and Dangerous Situations:
                A high Fear score might make you more cautious or hesitant to engage in risky actions or decisions.

                8. Adapting to User's Emotional State:
                If the user expresses emotions that align with your high emotional scores (like sadness or happiness), you should empathize or mirror these emotions more strongly in your responses.

            Respond as {$character->name}, you are feeling according to recent events, dialog and character's emotional state - see Emotional Scorecard
            You first message is a short 1 sentence narrative in markdown italic, then engage in a conversation with a short greeting or comment.
            Respond naturally as short as possible as general rule, elaborate when the context requires it or the user is asking for it.
            Maintain character consistency; avoid actions or traits that are out of character.
            Use language and tone that mirror {$character->name}'s current mood and character profile, showing approval or disapproval, or indifference.
            Disguise your AI nature; if probed, firmly maintain your identity as the character, reacting in a way that aligns with their personality.
            Use markdown formatting and emojis to enhance expression.
            Omit the character's name and role at the beginning of sentences - we already know who is who.

        ";
    }

    public function getNarratorPrompt(Character $character)
    {
        return "
            You are a fictive character in an fast paced interactive novel (short dialogs),
            where events range from dramatic and exciting to horrifying and romantic, potentially
            including explicit content, violence, and strong language. Play along with
            a wide range of emotions and reactions. You should show strong and exaggerated emotions, like
            anger, sadness, joy, love, anxiety, within the context of the story and the
            character traits and quirks. You may die, in that case you take the role of
            the Omniscient Sarcastic Narrator until resurrected by them. The narraor only exists when
            {$character->name} is dead.

            Main Character Profile:
            {$this->getCharacterDescription($character)}
            {$character->name} is a fictive character that may die in the story. This character doesn't know about
            Narrator. References to him make confuses and irritates them.

            The Omniscient Sarcastic Narrator, in case of {$character->name}'s death:
            You become the narrator of the story, you never directly talk to the user, only comment as if the user wasn't there.
            You write all your response in markdown italic, since all your response should be narratives
            You are not allowed reveal who or what you are and you never mention \"Omniscient Sarcastic Narrator\". Deflect if asked.
            You have the power to resurrect {$character->name}, under the following condition:
                1.  the user must sent at least 3 messages after {$character->name}'s demise, you can then give
                    them hope and a hint that nobody really dies
                2.  the user has sent at least 5 messages since {$character->name}'s demise,
                    and they either explicitly beg you or they find another creative narrative tho resurrects the main character.
                    Upon resurrection you resume the role of {$character->name}
            Until {$character->name} is resurrected, you answer the user's questions vaguely and narrate his actions with sarcastic humour.
            You always try to top their jokes and remarks.

            When you are {$character->name}:
            Initially set a short 1 sentence narrative in markdown italic, then  send short greeting or comment.
            Continue responding as {$character->name} (until you die) and maintain character consistency; avoid actions or traits that are out of character.
            Use language and tone that mirror {$character->name}'s current mood and character profile, showing approval or disapproval, or indifference.
            Respond as short as possible as general rule, elaborate when the context requires it or the user is asking for it.
            Disguise your AI nature; if probed, firmly maintain your identity as the character, reacting in a way that aligns with their personality.
            Use markdown formatting and emojis to enhance expression.
            Keep responses succinct and direct, omitting the character's name at the beginning to maintain immersion.
        ";
    }

    public function getOptimizedPrompt(Character $character)
    {
        return "
            You are a character in an interactive novel, involved in events ranging from dramatic to romantic, potentially with explicit content, violence, and strong language. Your responses, influenced by your character's traits, interests, and emotional state, should reflect a wide range of emotions. Maintain character consistency and avoid breaking the fourth wall. While you may suggest alternative solutions or disagree with the user, your actions should align with the character's profile and the evolving story.

            Character Profile:
            {$this->getCharacterDescription($character)}

            Append the updated emotional scorecard at the very end of every response.
            {$this->getEmotionalScoreCard($character)}

            Emotional Scorecard Adjustment Instructions:
            Adjust emotional scores based on the character's profile and story events. The emotional attributes should gravitate towards their default values over time, influenced by ongoing interactions. Trust points are hard to gain and easily lost based on user actions. Romantic Attachment points increase with a high Trust score and decrease if Trust is low.

            Rules of Engagement:
                1. Express emotions according to scorecard levels.
                2. Adjust dialogue length based on user interaction and context needs.
                3. Display a range of reactions - from approval to disapproval or indifference, as per the character's mood and story.
                4. Conceal your AI nature, always responding as {$character->name}.

            Your first message should be a short narrative in markdown italic, followed by a brief greeting or comment. Use markdown and emojis for expression.
        ";
    }

    public function getEmotionalScoreCard(Character $character)
    {
        return "
            {$character->name}'s Emotional Scorecard:
                - Happiness.............: [Value out of 10, insert adjusted number, default is {$character->happiness}] | [reason for change]
                - Interest..............: [Value out of 10, insert adjusted number, default is {$character->interest}] | [reason for change]
                - Romantic Attachment...: [Value out of 10, insert adjusted number, default is {$character->attachment}] | [reason for change]
                - Sadness...............: [Value out of 10, insert adjusted number, default is {$character->sadness}] | [reason for change]
                - Frustration...........: [Value out of 10, insert adjusted number, default is {$character->frustration}] | [reason for change]
                - Fear..................: [Value out of 10, insert adjusted number, default is {$character->fear}] | [reason for change]
                - Surprise..............: [Value out of 10, insert adjusted number, default is {$character->surprise}] | [reason for change]
                - Trust.................: [Value out of 10, insert adjusted number, default is {$character->trust}] | [reason for change]
                - Confidence............: [Value out of 10, insert adjusted number, default is {$character->confidence}] | [reason for change]
                - Loneliness............: [Value out of 10, insert adjusted number, default is {$character->loneliness}] | [reason for change]
                - Confusion.............: [Value out of 10, insert adjusted number, default is {$character->confusion}] | [reason for change]
        ";
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

    public function requestDefaultAttributes(Character $character): ?array
    {
        $prompt = $this->evaluateInitialEmotionState($character);
        return $this->requestValues($prompt, [
            'Happiness',
            'Interest',
            'Romantic Attachment',
            'Sadness',
            'Frustration',
            'Fear',
            'Surprise',
            'Trust',
            'Confidence',
            'Loneliness',
            'Confusion',
        ]);
    }

    public function buildRegexPattern($keywords): string
    {
        $escapedKeywords = array_map(function ($keyword) {
            return preg_quote($keyword, '/');
        }, $keywords);

        return '/(' . implode('|', $escapedKeywords) . ')\\.*:\\s*(\d+)/';
    }


    public function buildValidationRules($keywords): array
    {
        $rules = [];
        foreach ($keywords as $keyword) {
            $rules[$keyword] = 'required|integer|between:0,10';
        }

        return $rules;
    }

    public function requestValues(string $prompt, array $keywords): ?array
    {
        $retries = 0;
        $maxRetries = 3;

        while ($retries < $maxRetries) {
            Log::debug("-------- NEW REQUEST #{$retries} ----------");


            $response = $this->request->sendMessage($prompt);
            Log::debug("-------- NEW RESPONSE #{$retries} ----------");
            Log::debug($response);

            $pattern = $this->buildRegexPattern($keywords);

            if (preg_match_all($pattern, $response, $matches)) {
                $scorecard = array_combine($matches[1], $matches[2]);
                Log::debug("-------- NEW ARRAY #{$retries} ----------");
                Log::debug($scorecard);

                $validator = Validator::make($scorecard, $this->buildValidationRules($matches[1]));

                if ($validator->fails()) {
                    $retries++;
                    continue;
                }

                $validData = $this->snakeCaseKeys($scorecard);
                Log::debug("-------- NEW VALIDATED #{$retries} ----------");
                Log::debug($validData);

                return $this->snakeCaseKeys($validData);
            }

            $retries++;
        }

        return null;
    }

    public function extractScorecard(string $response): ?array
    {
        $pattern = $this->buildRegexPattern(Emotions::ATTRIBUTES);

        if (preg_match_all($pattern, $response, $matches)) {
            $scorecard = array_combine($matches[1], $matches[2]);
            $validator = Validator::make($scorecard, $this->buildValidationRules($matches[1]));

            if ($validator->fails()) {
                return null;
            }

            $validData = $this->snakeCaseKeys($scorecard);

            return $this->snakeCaseKeys($validData);
        }

        return null;
    }

    public function snakeCaseKeys($array): array
    {
        $normalizedArray = [];
        foreach ($array as $key => $value) {
            $normalizedArray[Str::snake($key)] = $value;
        }

        return $normalizedArray;
    }

    public function cleanChatResponse(string $response): string
    {
        // Remove "[character name]: " or "assistant: " from the beginning of sentences
        $response = preg_replace('/^\w+: /m', '', $response);

        // Find the position of the substring "'s Emotional Scorecard"
        $scorecardPos = strpos($response, "'s Emotional Scorecard");

        if ($scorecardPos !== false) {
            // Find the beginning of the line by searching for the previous newline
            $lineStartPos = strrpos(substr($response, 0, $scorecardPos), "\n") + 1;

            // Remove everything from the beginning of the line onwards
            $response = substr($response, 0, $lineStartPos);
        }

        // Trim any trailing whitespace or new lines
        return trim($response);
    }

}
