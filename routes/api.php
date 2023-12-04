<?php

use App\Models\ChatSession;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\UserController;
use App\Http\Controllers\Api\CharacterController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\SessionController;
use Laravel\Fortify\Http\Responses\LogoutResponse;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Broadcast::routes(['middleware' => ['auth:sanctum']]);


Route::post('/register', [RegisterController::class, 'register'])->middleware(['web']);
Route::post('/login', [LoginController::class, 'login'])->middleware(['web']);

Route::get('/user', function (Request $request) {
    return $request->user() ?? null;
})->middleware(['web']);;

Route::group(['middleware' => ['auth:sanctum']], function () {

    Route::get('/chat/{session}', [ChatController::class, 'getLatestMessages']);
    Route::post('/chat/{session}', [ChatController::class, 'sendMessage']);
    Route::post('/chat/retry', [ChatController::class, 'retry']);
    Route::get('/sessions', [ChatController::class, 'getSessions']);
    Route::apiResource('characters', CharacterController::class);
    Route::apiResource('messages', MessageController::class);
    Route::apiResource('sessions', SessionController::class);
    Route::post('/sessions/{character}', [SessionController::class, 'storeWithCharacter']);
    Route::post('/logout', [LogoutController::class, 'logout']);
});

//Route::get('/user', [UserController::class, 'currentUser'])->middleware(['auth:sanctum']);
//
//Route::get('/chat/{session}', [ChatController::class, 'getLatestMessages'])->middleware(['auth:sanctum']);
//Route::post('/chat/{session}', [ChatController::class, 'sendMessage'])->middleware(['auth:sanctum']);
//Route::post('/chat/retry', [ChatController::class, 'retry'])->middleware(['auth:sanctum']);
//
//Route::get('/sessions', [ChatController::class, 'getSessions'])->middleware(['auth:sanctum']);
//
//Route::apiResource('characters', CharacterController::class)->middleware(['auth:sanctum']);
//Route::apiResource('messages', MessageController::class)->middleware(['auth:sanctum']);
//Route::apiResource('sessions', SessionController::class)->middleware(['auth:sanctum']);
//Route::post('/sessions/{character}', [SessionController::class, 'storeWithCharacter'])->middleware(['auth:sanctum']);



