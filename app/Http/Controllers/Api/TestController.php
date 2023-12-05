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
use Intervention\Image\Facades\Image;

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


    }
}
