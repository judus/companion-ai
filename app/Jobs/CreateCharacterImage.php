<?php

namespace App\Jobs;

use App\Models\Character;
use App\Services\DallERequest;
use App\Services\OpenAIPrompts;
use App\Services\OpenAIRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class CreateCharacterImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $characterId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $characterId
    ) {
        $this->characterId = $characterId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $dalle = app(DallERequest::class);
        $prompts = app(OpenAIPrompts::class);
        $openAIRequest = app(OpenAIRequest::class);

        $character = Character::find($this->characterId);
        $description = $prompts->getCharacterDescription($character);

        $response = $openAIRequest->sendMessage(
            "Summarize a short prompt, max. 1-2 sentence for Dall-E to create a portrait that fits the character and matches the image style:
            - {$description}
            Image style:
            - Centered Portrait, Filling the frame,
            - Modern Graphic Novel, Bold Contouring, Flat Colors with Gradient Shading, Limited Color Palette
            - Background: solid color with a subtle gradient
            Example prompts:
            - A portrait of an elderly person, capturing the wrinkles, texture of the skin, and expressive eyes that tell a story of a lifetime, in high resolution and natural lighting.
            - A portrait of a mysterious woman sitting in an ornate, gothic throne room, with subtle elements of fantasy and magical realism, painted with the dark and dramatic style of the Baroque period.
            ");

        $dalle->setModel("dall-e-3");

        $response = $dalle->sendRequest("
            $response
        ");

        if ($response instanceof Collection) {
            return;
        }


        $url = $response->url;
        $imageContent = Http::get($url)->body();

        $filename = Str::random(32) . '.png';
        $storagePath = 'images/' . $filename; // Define a path in your storage disk
        Storage::disk('gcs')->put($storagePath, $imageContent);
        //Storage::disk('gcs')->put($storagePath, $imageContent);

        $this->resizeImage($storagePath, 120, 120);
        $this->resizeImage($storagePath, 480, 480);

        $character->image_url = $storagePath;
        $character->save();
    }

    public function resizeImage($storagePath, $width, $height)
    {
        $image = Image::make(Storage::disk('gcs')->get($storagePath));
        $filename = Str::replaceEnd(".png", ".jpg", basename($storagePath));
        $image->resize($width, $height, function ($constraint) {
            $constraint->aspectRatio();
        });

        Storage::disk('gcs')->put("images/_{$width}x{$height}/{$filename}", $image->encode('jpg'));
    }

}
