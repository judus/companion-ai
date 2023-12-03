<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public function success($data, $message = null, $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
            'data' => $data
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        return response()->json($response, $statusCode);
    }


    public function error($message, $statusCode = 400, $details = null): JsonResponse
    {
        $error = [
            'code' => $statusCode
        ];

        if ($details !== null) {
            $error['details'] = $details;
        }

        $response = [
            'success' => false,
            'error' => $error
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        return response()->json($response, $statusCode);
    }
}
