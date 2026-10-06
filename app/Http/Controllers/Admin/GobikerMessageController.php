<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GobikerMessage;
use Illuminate\Http\Request;

class GobikerMessageController extends Controller
{
    public function index(Request $request)
    {
        $messages = GobikerMessage::with('sender')
            ->when($request->search, function ($q, $s) {
                $q->whereHas('sender', fn ($u) => $u->where('name', 'like', "%{$s}%"))
                  ->orWhere('message', 'like', "%{$s}%");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.operations.gobiker-messages.index', compact('messages'));
    }

    public function markRead(GobikerMessage $message)
    {
        if (! $message->is_read) {
            $message->update(['is_read' => true]);
        }

        return response()->json(['is_read' => true]);
    }

    public function destroy(GobikerMessage $message)
    {
        $message->delete();

        return redirect()->route('admin.operations.gobiker-messages.index')
            ->with('success', 'Message deleted.');
    }
}
