<?php

namespace App\Http\Controllers;

use App\Models\Character;
use Illuminate\Http\Request;

class CharacterController extends Controller
{


    public function index()
    {
        return Character::orderBy('name', 'asc')->get();
    }

    public function get(Request $request)
    {
        return Character::where('id', $request->get('id'))->with('chatSessions')->first();
    }

    public function save(Request $request)
    {
        // Validate the request data
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'age' => 'nullable|integer',
            'gender' => 'nullable|string|max:255',
            'bio' => 'nullable|string',
            'occupation' => 'nullable|string|max:255',
            'traits' => 'nullable|string',
            'quirks' => 'nullable|string',
            'interests' => 'nullable|string',
            'location' => 'nullable|string|max:255',
            'dialogueStyle' => 'nullable|string|max:255',
        ]);

        $validatedData['user_id'] = $request->user()->id;

        // Create a new character
        $character = Character::create($validatedData);



        // Return a response
        return response()->json([
            'message' => 'CharacterService created successfully',
            'character' => $character
        ]);
    }


}
