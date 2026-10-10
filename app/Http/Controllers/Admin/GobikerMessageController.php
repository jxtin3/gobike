<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GobikerMessage;
use Illuminate\Http\Request;

class GobikerMessageController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = in_array($request->query('status'), ['read', 'unread'], true)
            ? $request->query('status')
            : null;

        $messages = GobikerMessage::with('sender')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->whereHas('sender', fn ($sender) => $sender->where('name', 'like', "%{$search}%"))
                        ->orWhere('message', 'like', "%{$search}%");
                });
            })
            ->when($statusFilter !== null, fn ($query) => $query->where('is_read', $statusFilter === 'read'))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.operations.gobiker-messages.index', [
            'messages' => $messages,
            'readMessages' => GobikerMessage::where('is_read', true)->count(),
            'unreadMessages' => GobikerMessage::where('is_read', false)->count(),
            'statusFilter' => $statusFilter,
        ]);
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
