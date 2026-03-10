<x-app-layout>

    <div class="p-6">

        <h2 class="text-xl font-bold mb-4">Messenger</h2>

        @if(session('success'))
        <div class="mb-4 text-green-600">
            {{ session('success') }}
        </div>
        @endif

        <h3 class="font-semibold mb-2">Send Message</h3>

        <form method="POST" action="{{ route('send.message') }}">
            @csrf

            <div class="mb-2">
                <label>User ID</label>
                <input type="number" name="user_id" class="border p-2 w-full">
            </div>

            <div class="mb-2">
                <label>Message</label>
                <textarea name="message" class="border p-2 w-full"></textarea>
            </div>

            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded shadow">
                Send Message
            </button>
        </form>

        <hr class="my-6">

        <h3 class="font-semibold mb-2">Your Threads</h3>

        @forelse($threads as $thread)
        <div class="border p-3 mb-2">
            Thread ID: {{ $thread->id }} <br>
            Last Message: {{ $thread->latestMessage?->body ?? 'No messages yet' }}
            <br>
            Sent At: {{ $thread->latestMessage?->created_at?->format('d M Y H:i') }}
        </div>
        @empty
        <p class="text-gray-500">No conversations yet.</p>
        @endforelse

    </div>

</x-app-layout>