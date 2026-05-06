<x-app-layout>

<div class="h-screen flex bg-gray-100">

    <!-- LEFT SIDEBAR (Users) -->
    <div class="w-1/4 bg-white border-r flex flex-col">
        <div class="p-4 border-b">
            <h2 class="font-semibold text-gray-700">Users</h2>

            <form method="GET" class="mt-2">
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="Search users..."
                    class="w-full px-3 py-2 border rounded-md text-sm focus:ring focus:ring-blue-200">
            </form>
        </div>

        <div class="flex-1 overflow-y-auto">
            @foreach($users as $user)
                <div class="flex items-center gap-3 px-4 py-3 border-b hover:bg-gray-100 cursor-pointer">
                    <div class="w-9 h-9 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold">
                        {{ strtoupper(substr($user->name,0,1)) }}
                    </div>
                    <div class="text-sm font-medium text-gray-700">
                        {{ $user->name }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- MIDDLE (SELECT USER + SEND) -->
    <div class="w-1/4 bg-gray-50 border-r flex flex-col">

        <div class="p-4 border-b">
            <h2 class="font-semibold text-gray-700">Send Message</h2>
        </div>

        <div class="p-4">

            @if(session('success'))
                <div class="bg-green-100 text-green-700 p-2 mb-3 rounded text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <form method="POST" action="{{ route('send.message') }}">
                @csrf

                <select name="user_id"
                    class="w-full px-3 py-2 border rounded-md mb-3 text-sm">
                    <option value="">Select User</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>

                <textarea name="message"
                    placeholder="Type message..."
                    class="w-full px-3 py-2 border rounded-md mb-3 text-sm h-28"></textarea>

                <button class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-md text-sm">
                    Send
                </button>
            </form>
        </div>
    </div>

    <!-- RIGHT SIDE (CHAT / THREADS) -->
    <div class="w-2/4 bg-white flex flex-col">

        <div class="p-4 border-b">
            <h2 class="font-semibold text-gray-700">Chats</h2>
        </div>

        <div class="flex-1 overflow-y-auto p-4">

            @forelse($threads as $thread)
                <div class="mb-3 p-3 border rounded-lg hover:bg-gray-50">
                    <div class="text-xs text-gray-500">
                        Thread #{{ $thread->id }}
                    </div>

                    <div class="text-sm font-medium text-gray-800">
                        {{ $thread->latestMessage?->body ?? 'No messages' }}
                    </div>

                    <div class="text-xs text-gray-400">
                        {{ $thread->latestMessage?->created_at?->diffForHumans() }}
                    </div>
                </div>
            @empty
                <p class="text-gray-400 text-center mt-10">
                    No conversations yet
                </p>
            @endforelse

        </div>

    </div>

</div>

</x-app-layout>