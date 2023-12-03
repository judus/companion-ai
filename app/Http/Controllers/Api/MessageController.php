<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Services\ApiResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    private ApiResponse $api;

    public function __construct(ApiResponse $apiResponse)
    {
        $this->api = $apiResponse;
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
        //
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
    public function destroy(Request $request, ChatMessage $message)
    {
        $messages = ChatMessage::where('chat_session_id', $message->chat_session_id)
            ->where('created_at', '>', $message->created_at)
            ->delete();

        $message->delete();

        return $this->api->success($messages, 'Message deleted');
    }
}
