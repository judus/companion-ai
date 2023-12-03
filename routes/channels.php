<?php

use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('my-channel', function () {
    return true;
});

Broadcast::channel('session.{userId}.{sessionId}', function (User $user, $userId, $sessionId) {
    $session = ChatSession::find($sessionId);
    Log::debug('yay!');

    return $session &&
        (int)$user->id === (int)$userId &&
        (int)$user->id === (int)$session->user_id;
});

