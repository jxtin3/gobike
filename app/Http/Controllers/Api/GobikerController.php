<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GobikerMessage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GobikerController extends Controller
{
    public function sendMessage(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        GobikerMessage::create([
            'user_id' => $request->user()->id,
            'message' => $data['message'],
        ]);

        return response()->json(['message' => 'Message sent to admin.']);
    }
}
