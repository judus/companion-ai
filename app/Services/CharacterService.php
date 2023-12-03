<?php

namespace App\Services;

use App\Jobs\CreateCharacterImage;
use App\Jobs\GenerateStoryJob;
use App\Models\Character;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CharacterService
{
    private $user;

    public function __construct()
    {
        $this->user = auth()->user();
    }

    public function createCharacter(array $data): Character
    {
        $character = Character::create($data);

        $this->queueImageCreation($character);
        //$this->queueStoryGeneration($character);

        return $character;
    }

    public function updateCharacter(Character $character, array $data): Character
    {
        $character->update($data);

        return $character;
    }

    public function deleteCharacter(Character $character): ?bool
    {
        return $character->delete();
    }

    public function getById(int $id): ?Character
    {
        return Character::find($id);
    }

    public function getByIdWithSessions(int $id): ?Character
    {
        return Character::where('id', $id)->with([
            'chatSessions' => function($query) {
                $query->where('user_id', Auth::id());
                $query->orderBy('created_at', 'desc');
            }
        ])->first();

    }

    protected function queueStoryGeneration(Character $character): CharacterService
    {
        GenerateStoryJob::dispatch($character);

        return $this;
    }

    protected function queueImageCreation(Character $character): CharacterService
    {
        dispatch(new CreateCharacterImage($character->id));

        return $this;
    }

    public function createRandomCharacter(): ?Character
    {
        $request = app(OpenAIRequest::class);

        $response = $request->sendMessage("
            Based on this schema of characters, generate a JSON array for 3 new character:
                \$table->string('name');
                \$table->integer('age')->nullable();
                \$table->string('short_description')->nullable();
                \$table->text('biography')->nullable();
                \$table->string('occupation')->nullable();
                \$table->string('traits')->nullable();
                \$table->string('quirks')->nullable();
                \$table->text('interests')->nullable();
                \$table->string('location')->nullable();
                \$table->string('dialogue_style')->nullable();

            Instructions:
            - The Character can not be \"Luna\"
            - The character can be anything from person, to animal, to object or vegetable and fruit, to abstract concept.
            - Make the character interesting and unique. It can be absurd, funny, amorous, serious, dark, menacing, unfriendly or anything else.
            - A character could be for example, a hat who enjoys hairless heads, or a vegetables like a tomato who fears to became ketchup or animals or a ghost.
            - The biography should be 3-6 sentences. Mention pivotal events or career highlights and underline traits, quirks and interest.
            - The quirks should be absurd and funny, picked with dark humour

             Your response must be a valid JSON array:
             [
                 {
                    \"name\": ...
                    \"age\": ...
                    ...etc
                 },
                 {
                    \"name\": ...
                    \"age\": ...
                    ...etc
                 },
                 ...etc
             ]
        ");


        Log::debug($response);


        if ($characters = json_decode($response, true)) {


            if (is_array($characters)) {
                foreach ($characters as $character) {

                    $character['gender'] = $character['short_description'];
                    $character['bio'] = $character['biography'];

                    $validator = Validator::make($character, [
                        'name' => 'required|string|max:255',
                        'age' => 'nullable|integer',
                        'gender' => 'nullable|string|max:255',
                        'bio' => 'nullable|string',
                        'occupation' => 'nullable|string|max:255',
                        'traits' => 'nullable|string',
                        'quirks' => 'nullable|string',
                        'interests' => 'nullable|string',
                        'location' => 'nullable|string|max:255',
                        'dialogue_style' => 'nullable|string|max:255',
                    ]);

                    if ($validator->fails()) {
                        $errors = $validator->errors();
                    } else {
                        $validatedData = $validator->validated();
                    }

                    $validatedData['user_id'] = 1;
                    $validatedData['is_public'] = true;

                    $this->createCharacter($validatedData);
                }
            }
        }

        return null;
    }

}
