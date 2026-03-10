<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use RTippin\Messenger\Facades\MessengerComposer;
use RTippin\Messenger\Models\Thread; //  Import this

class MessengerController extends Controller
{
public function index()
{
    $user = auth()->user();

    // Eager load the latestMessage relation
    $threads = Thread::whereHas('participants', function ($query) use ($user) {
        $query->where('owner_id', $user->id)
              ->where('owner_type', get_class($user));
    })->with('latestMessage') //  eager load last message
      ->latest('updated_at')
      ->get();

    return view('messenger.index', compact('threads'));
}

  public function sendMessage(Request $request)
{
    $request->validate([
        'user_id' => 'required|exists:users,id',
        'message' => 'required',
    ]);

    $sender = auth()->user();
    $receiver = User::findOrFail($request->user_id);

    // Use MessengerComposer to send a private message
    MessengerComposer::to($receiver)
        ->from($sender)
        ->message($request->message);

    return back()->with('success', 'Message sent successfully!');
}
}