<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function currentUser(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
