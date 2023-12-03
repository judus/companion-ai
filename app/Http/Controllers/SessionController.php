<?php

namespace App\Http\Controllers;

use App\Models\ChatSession;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function create(Request $request)
    {
        $validatedData = $request->validate([
            'character_id' => 'required|integer',
        ]);

        $validatedData['user_id'] = $request->user()->id;

        return ChatSession::create($validatedData);
    }
}
