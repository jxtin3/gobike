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

    /** The GoBiker's messages with any admin replies, newest first. */
    public function messages(Request $request): JsonResponse
    {
        $messages = GobikerMessage::where('user_id', $request->user()->id)
            ->latest()
            ->latest('id')
            ->limit(50)
            ->get();

        return response()->json([
            'messages' => $messages->map(fn (GobikerMessage $m) => [
                'id' => $m->id,
                'message' => $m->message,
                // The admin started this one: 'message' is the admin's text, shown as theirs.
                'from_admin' => (bool) $m->from_admin,
                'sent_at' => $m->created_at?->toIso8601String(),
                'reply' => $m->reply_message,
                'replied_at' => $m->replied_at?->toIso8601String(),
                'replied_by' => $m->adminReply?->name,
            ])->values(),
        ]);
    }
}
