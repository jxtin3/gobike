<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactMessageController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = in_array($request->query('status'), ['read', 'unread'], true)
            ? $request->query('status')
            : null;

        $messages = ContactMessage::query()
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('message', 'like', "%{$search}%");
                });
            })
            ->when($statusFilter !== null, fn ($query) => $query->where('is_read', $statusFilter === 'read'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.operations.messages.index', [
            'messages' => $messages,
            'statusFilter' => $statusFilter,
            'totalCount' => ContactMessage::count(),
            'unreadCount' => ContactMessage::where('is_read', false)->count(),
        ]);
    }

    public function markRead(ContactMessage $message)
    {
        if (! $message->is_read) {
            $message->update(['is_read' => true]);
        }

        return response()->json(['is_read' => true]);
    }

    public function destroy(ContactMessage $message)
    {
        $message->delete();

        return back()->with('success', 'Message deleted.');
    }
}
