<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Character;
use App\Models\ChatSession;
use App\Services\ApiResponse;
use App\Services\Emotions;
use App\Services\SessionService;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    private ApiResponse $api;
    private SessionService $sessionService;

    public function __construct(ApiResponse $apiResponse, SessionService $session)
    {
        $this->api = $apiResponse;
        $this->sessionService = $session;
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

    }

    public function storeWithCharacter(Request $request, Character $character)
    {
        // Validate that the character belongs to the authenticated user
        if (!$character->is_public && $character->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized action.');
        }

        $session = $this->sessionService->createNewSession($request->user(), $character);

        return $this->api->success([
            'session' => $session,
        ], 'Session created', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
