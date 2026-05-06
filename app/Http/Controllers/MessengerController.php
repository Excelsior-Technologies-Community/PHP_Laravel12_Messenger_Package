<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use RTippin\Messenger\Facades\MessengerComposer;
use RTippin\Messenger\Models\Thread;

class MessengerController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // Search users
        $search = $request->search;

        $users = User::where('id', '!=', $user->id)
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
            })
            ->get();

        // Get threads
        $threads = Thread::whereHas('participants', function ($query) use ($user) {
            $query->where('owner_id', $user->id)
                  ->where('owner_type', get_class($user));
        })
        ->with(['latestMessage', 'participants'])
        ->latest('updated_at')
        ->get();

        return view('messenger.index', compact('threads', 'users', 'search'));
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'message' => 'required',
        ]);

        $sender = auth()->user();
        $receiver = User::findOrFail($request->user_id);

        MessengerComposer::to($receiver)
            ->from($sender)
            ->message($request->message);

        return back()->with('success', 'Message sent successfully!');
    }
}