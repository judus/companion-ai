<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCharacterRequest;
use App\Http\Requests\UpdateCharacterRequest;
use App\Models\Character;
use App\Services\ApiResponse;
use App\Services\CharacterService;
use App\Services\OpenAI;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CharacterController extends Controller
{
    private ApiResponse $api;
    private CharacterService $character;
    private OpenAI $openAI;

    public function __construct(
        ApiResponse $apiResponse,
        CharacterService $characterService,
        OpenAI $openAI
    ) {
        $this->api = $apiResponse;
        $this->character = $characterService;
        $this->openAI = $openAI;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        return $this->api->success(Character::where('is_public', true)
            ->orWhere('user_id', $request->user()->id)->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCharacterRequest $request): JsonResponse
    {
        // The incoming request data is already validated
        $validatedData = $request->validated();

        // Set 'user_id' to the ID of the currently authenticated user
        $validatedData['user_id'] = $request->user()->id;

        // Create a new character
        $character = $this->character->createCharacter($validatedData);

        if ($attributes = $this->openAI->requestDefaultAttributes($character)) {
            $character = $this->character->updateCharacter($character, $attributes)->refresh();
        }

        // Return a response
        return $this->api->success($character->refresh(), 'Character created', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Character $character): JsonResponse
    {
        $character = $this->character->getByIdWithSessions(
            $character->id
        );

        return $this->api->success($character);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCharacterRequest $request, Character $character): JsonResponse
    {
        $this->character->updateCharacter($character, $request->validated());

        return $this->api->success($character, 'Character updated');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Character $character): JsonResponse
    {
        //$this->character->deleteCharacter($character);

        return $this->api->success(null, 'Character deleted');
    }
}
