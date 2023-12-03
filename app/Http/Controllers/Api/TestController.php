<?php

namespace App\Http\Controllers\Api;

use App\Jobs\CreateCharacterImage;
use App\Jobs\CreateRandomCharacter;
use App\Models\Character;
use App\Services\CharacterService;
use App\Services\DallERequest;
use App\Services\OpenAI;
use App\Services\OpenAIPrompts;
use App\Services\OpenAIRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TestController
{
    private DallERequest $dalle;
    private CharacterService $character;
    private OpenAI $openAI;
    private OpenAIPrompts $prompts;
    private OpenAIRequest $openAIRequest;

    public function __construct(
        DallERequest $dalle,
        CharacterService $characterService,
        OpenAI $openAI,
        OpenAIPrompts $prompts,
        OpenAIRequest $openAIRequest
    ) {
        $this->dalle = $dalle;
        $this->character = $characterService;
        $this->openAI = $openAI;
        $this->prompts = $prompts;
        $this->openAIRequest = $openAIRequest;
    }

    public function test()
    {

//        for($i = 0; $i < 10; $i++) {
//            dispatch(new CreateRandomCharacter());
//        }


        $localFiles = Storage::disk('public')->files('images');

        foreach ($localFiles as $file) {
            $contents = Storage::disk('public')->get($file);
            $destination = 'images/' . basename($file);
            dump('moving ' . $file . ' to ' . $destination);

            Storage::disk('gcs')->put($destination, $contents);
        }





//        return response()->json([]);

//        $character = Character::find(5);
//        $description = $this->prompts->getCharacterDescription($character);
//
//        $response = $this->openAIRequest->sendMessage(
//            "Summarize a short prompt, max. 1-2 sentence for Dall-E to create a portrait that fits the character and matches the image style:
//            - {$description}
//            Image style:
//            - Centered Portrait, Filling the frame,
//            - Modern Graphic Novel, Bold Contouring, Flat Colors with Gradient Shading, Limited Color Palette
//            - Background: solid color with a subtle gradient
//            Example prompts:
//            - A portrait of an elderly person, capturing the wrinkles, texture of the skin, and expressive eyes that tell a story of a lifetime, in high resolution and natural lighting.
//            - A portrait of a mysterious woman sitting in an ornate, gothic throne room, with subtle elements of fantasy and magical realism, painted with the dark and dramatic style of the Baroque period.
//            ");
//
//
//        //$this->dalle->setSize("512x512");
//        $this->dalle->setModel("dall-e-3");
//
//        $response = $this->dalle->sendRequest("
//            $response
//        ");
//
//        if ($response instanceof Collection) {
//            return response()->json($response);
//        }
//
//
//        $url = $response->url;
//
//        $imageContent = Http::get($url)->body();
//
//        $filename = Str::random(24) . '.png';
//        $storagePath = 'images/' . $filename; // Define a path in your storage disk
//        Storage::disk('public')->put($storagePath, $imageContent);
//
//        $character->image_url = $storagePath;
//        $character->save();
//
//        $publicUrl = Storage::disk('public')->url($storagePath);
//        $file = Storage::disk('public')->get($storagePath);
//        $type = Storage::disk('public')->mimeType($storagePath);
//
//        return response()->json($character);
//        return response($file, 200)->header('Content-Type', $type);

    }
}
