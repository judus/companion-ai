<?php

use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\TestController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', [TestController::class, 'test']);

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});


Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF token set']);
})->middleware('web');

Route::get('/auth/google', function () {
    return Socialite::driver('google')->redirect();
});

Route::get('/auth/google/callback', function (\Illuminate\Http\Request $request) {
    $googleUser = Socialite::driver('google')->user();

    $user = User::where('email', $googleUser->email)->first();

    if ($user) {
        $user->update([
            'service_id' => $googleUser->id,
            'service_type' => 'google',
        ]);
    } else {
        // New user, create account
        $user = User::create([
            'name' => $googleUser->name,
            'email' => $googleUser->email,
            'password' => Hash::make(Str::random(12)),
            'service_id' => $googleUser->id,
            'service_type' => 'google',
            // Set other fields like password or email_verified_at if necessary
        ]);
    }

    Auth::login($user);

    return redirect(env('SPA_URL'));
});
