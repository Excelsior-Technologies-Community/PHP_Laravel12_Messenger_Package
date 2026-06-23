<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use RTippin\Messenger\Facades\MessengerComposer;
use RTippin\Messenger\Models\Thread;
use RTippin\Messenger\Models\Message;
use RTippin\Messenger\Messenger;
use RTippin\Messenger\Support\MessageTransformer;

class MessengerController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $search = $request->search;

        $users = User::where('id', '!=', $user->id)
            ->when($search, function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->limit(20)
            ->get();

        $threads = Thread::whereHas('participants', function ($query) use ($user) {
            $query->where('owner_id', $user->id)
                ->where('owner_type', get_class($user));
        })
        ->with(['latestMessage', 'participants.owner'])
        ->latest('updated_at')
        ->get();

        return view('messenger.index', compact('threads', 'users', 'search'));
    }

    public function sendMessageAjax(Request $request)
    {
        try {
            $request->validate([
                'thread_id' => 'required|exists:threads,id',
                'message' => 'nullable|string|max:5000',
                'attachments.*' => 'nullable|file|max:10240',
            ]);

            $sender = auth()->user();
            $thread = Thread::findOrFail($request->thread_id);

            $isParticipant = $thread->participants()
                ->where('owner_id', $sender->id)
                ->where('owner_type', get_class($sender))
                ->exists();

            if (!$isParticipant) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not a participant in this thread'
                ], 403);
            }

            $composer = MessengerComposer::to($thread)->from($sender);

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    if ($file->isValid()) {
                        $path = $file->store('messenger/attachments', 'public');
                        $fullPath = storage_path('app/public/' . $path);
                        
                        if (str_starts_with($file->getMimeType(), 'image/')) {
                            $composer->image($file);
                        } else {
                            $composer->document($file);
                        }
                    }
                }
            }

            $composer->message($request->message ?? '');

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully!',
                'thread_id' => $thread->id
            ]);

        } catch (\Exception $e) {
            Log::error('Send message error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getMessages($threadId)
    {
        try {
            $user = auth()->user();
            $thread = Thread::with(['messages.owner', 'participants.owner'])
                ->findOrFail($threadId);

            $isParticipant = $thread->participants()
                ->where('owner_id', $user->id)
                ->where('owner_type', get_class($user))
                ->exists();

            if (!$isParticipant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $messages = $thread->messages()
                ->with('owner')
                ->orderBy('created_at', 'asc')
                ->limit(100)
                ->get();

            $formattedMessages = $messages->map(function($msg) {
                $body = $msg->body ?? '';
                $attachmentData = null;
                $displayBody = $body;
                
                // Check if message is an image (Messenger stores image paths in body)
                if (preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $body)) {
                    $attachmentData = [
                        'type' => 'image',
                        'url' => asset('storage/' . $body),
                        'name' => basename($body)
                    ];
                    $displayBody = ''; // Don't show path as text
                } 
                // Check if message is a document
                elseif (preg_match('/\.(pdf|doc|docx|xls|xlsx|txt|zip|rar)$/i', $body)) {
                    $attachmentData = [
                        'type' => 'document',
                        'url' => asset('storage/' . $body),
                        'name' => basename($body)
                    ];
                    $displayBody = ''; // Don't show path as text
                }
                // Check if body contains storage path
                elseif (str_contains($body, 'messenger/') || str_contains($body, 'storage/')) {
                    $fileName = basename($body);
                    if (preg_match('/\.(jpg|jpeg|png|gif|webp|svg)$/i', $body)) {
                        $attachmentData = [
                            'type' => 'image',
                            'url' => asset('storage/' . $body),
                            'name' => $fileName
                        ];
                        $displayBody = '';
                    } elseif (preg_match('/\.(pdf|doc|docx|xls|xlsx|txt|zip|rar)$/i', $body)) {
                        $attachmentData = [
                            'type' => 'document',
                            'url' => asset('storage/' . $body),
                            'name' => $fileName
                        ];
                        $displayBody = '';
                    }
                }

                return [
                    'id' => $msg->id,
                    'body' => $displayBody,
                    'sender_id' => $msg->owner_id,
                    'sender_name' => $msg->owner?->name ?? 'Unknown',
                    'created_at' => $msg->created_at,
                    'time' => $msg->created_at?->format('h:i A') ?? '',
                    'time_ago' => $msg->created_at?->diffForHumans() ?? '',
                    'is_sender' => $msg->owner_id === auth()->id(),
                    'attachment' => $attachmentData,
                    'raw_body' => $body, // For debugging
                ];
            });

            return response()->json([
                'success' => true,
                'messages' => $formattedMessages,
                'thread_id' => $thread->id
            ]);

        } catch (\Exception $e) {
            Log::error('Get messages error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:5000',
        ]);

        $sender = auth()->user();
        $receiver = User::findOrFail($request->user_id);

        MessengerComposer::to($receiver)
            ->from($sender)
            ->message($request->message ?? '');

        return back()->with('success', 'Message sent successfully!');
    }

    public function getOrCreateThread(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        $sender = auth()->user();
        $receiver = User::findOrFail($request->user_id);

        $existingThread = Thread::where('type', 1)
            ->whereHas('participants', function($query) use ($sender) {
                $query->where('owner_id', $sender->id)
                    ->where('owner_type', get_class($sender));
            })
            ->whereHas('participants', function($query) use ($receiver) {
                $query->where('owner_id', $receiver->id)
                    ->where('owner_type', get_class($receiver));
            })
            ->first();

        if ($existingThread) {
            return response()->json([
                'success' => true,
                'thread_id' => $existingThread->id,
                'exists' => true
            ]);
        }

        MessengerComposer::to($receiver)
            ->from($sender)
            ->message('Hello! 👋');

        $newThread = Thread::where('type', 1)
            ->whereHas('participants', function($query) use ($sender) {
                $query->where('owner_id', $sender->id);
            })
            ->whereHas('participants', function($query) use ($receiver) {
                $query->where('owner_id', $receiver->id);
            })
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'thread_id' => $newThread->id,
            'exists' => false
        ]);
    }

    public function markAsRead(Request $request)
    {
        $request->validate([
            'thread_id' => 'required|exists:threads,id'
        ]);

        try {
            $user = auth()->user();
            
            Message::where('thread_id', $request->thread_id)
                ->where('owner_id', '!=', $user->id)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteMessage($messageId)
    {
        try {
            $user = auth()->user();
            $message = Message::findOrFail($messageId);

            if ($message->owner_id != $user->id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
            }

            $message->delete();
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function searchUsers(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1'
        ]);

        $user = auth()->user();

        $users = User::where('id', '!=', $user->id)
            ->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->query . '%')
                  ->orWhere('email', 'like', '%' . $request->query . '%');
            })
            ->limit(10)
            ->get()
            ->map(function($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'initial' => strtoupper(substr($u->name, 0, 1)),
                ];
            });

        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }

    public function typingIndicator(Request $request)
    {
        $request->validate([
            'thread_id' => 'required|exists:threads,id',
            'is_typing' => 'required|boolean'
        ]);

        return response()->json(['success' => true]);
    }

    public function uploadAttachment(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240',
            'thread_id' => 'required|exists:threads,id'
        ]);

        try {
            $file = $request->file('file');
            $path = $file->store('messenger/attachments', 'public');

            return response()->json([
                'success' => true,
                'path' => $path,
                'url' => asset('storage/' . $path),
                'name' => $file->getClientOriginalName(),
                'type' => str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'document'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}