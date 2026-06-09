<?php

namespace App\Http\Controllers\Binary;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApiUploadBinaryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $token = config('perry.binary_upload_token');

        if (! $token || $request->bearerToken() !== $token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $request->validate([
            'binary' => ['required', 'file', 'max:102400'],
        ]);

        $content = $request->file('binary')->getContent();
        $hash = hash('sha256', $content);

        Storage::disk('local')->put('perry', $content);
        Storage::disk('local')->put('perry.sha256', $hash);

        return response()->json(['hash' => $hash]);
    }
}
