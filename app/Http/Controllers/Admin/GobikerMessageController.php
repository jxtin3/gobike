<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GobikerMessage;
use App\Models\User;
use App\Services\Push\PushNotifier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
            'totalCount' => GobikerMessage::count(),
            'unreadCount' => GobikerMessage::where('is_read', false)->count(),
            'statusFilter' => $statusFilter,
            // For "New message": every active GoBiker, whether or not they ever wrote.
            'gobikers' => User::where('role', 'GoBiker')->where('status', 'Active')
                ->orderBy('name')
                ->get(['id', 'name', 'barangay']),
        ]);
    }

    public function markRead(GobikerMessage $message)
    {
        if (! $message->is_read) {
            $message->update(['is_read' => true]);
        }

        return response()->json(['is_read' => true]);
    }

    /**
     * The admin starts a message to a GoBiker who has not written. Stored like a GoBiker's row
     * (user_id is the GoBiker) with from_admin set and `message` holding the admin's text. It is
     * already "read": nothing in it waits for the admin.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'GoBiker')->where('status', 'Active')],
            'message' => ['required', 'string', 'max:1000'],
        ], [
            'user_id.exists' => 'Choose an active GoBiker.',
        ]);

        $message = GobikerMessage::create([
            'user_id' => $data['user_id'],
            'message' => trim($data['message']),
            'from_admin' => true,
            'is_read' => true,
            'reply_from_admin_id' => $request->user()->id,
            'replied_at' => now(),
        ]);
        app(PushNotifier::class)->gobikerMessageFromAdmin($message->load('sender'));

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Message sent.', 'id' => $message->id], 201);
        }

        return back()->with('success', 'Message sent to GoBiker.');
    }

    public function reply(Request $request, GobikerMessage $message)
    {
        $data = $request->validate([
            'reply_message' => ['required', 'string', 'max:1000'],
        ]);

        // The admin wrote this one: there is nothing to answer. A follow-up is a new message.
        if ($message->from_admin) {
            throw ValidationException::withMessages(['reply_message' => 'This message is yours. Send a new message instead.']);
        }

        $message->update([
            'is_read' => true,
            'reply_from_admin_id' => $request->user()->id,
            'reply_message' => $data['reply_message'],
            'replied_at' => now(),
        ]);

        $message = $message->fresh(['sender', 'adminReply']);
        app(PushNotifier::class)->gobikerMessageReply($message);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Reply sent.',
                'reply_message' => $message->reply_message,
                'replied_at' => $message->replied_at?->format('M j, Y g:i A'),
                'replied_by' => $message->adminReply?->name,
            ]);
        }

        return back()->with('success', 'Reply sent to GoBiker.');
    }

    public function destroy(GobikerMessage $message)
    {
        $message->delete();

        return back()->with('success', 'Message deleted.');
    }
}
