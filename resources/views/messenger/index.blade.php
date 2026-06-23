<x-app-layout>
    <div class="h-screen flex overflow-hidden bg-gray-100" x-data="{ activeThread: null, sidebarOpen: false }">
        
        <!-- Sidebar -->
        <div class="w-full md:w-1/3 lg:w-1/4 bg-white border-r flex flex-col h-full" 
             :class="{ 'fixed inset-0 z-50': sidebarOpen, 'hidden md:flex': !sidebarOpen }" 
             id="sidebar">
            
            <!-- Header -->
            <div class="p-4 bg-blue-600 text-white font-bold text-lg shadow-sm flex items-center justify-between flex-shrink-0">
                <span><svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg> Chats</span>
                <span class="text-xs bg-blue-500 px-3 py-1 rounded-full">{{ $threads->count() }}</span>
            </div>

            <!-- Search -->
            <div class="p-3 bg-gray-50 border-b flex-shrink-0">
                <form method="GET" action="{{ route('messenger') }}" class="relative">
                    <input type="text" name="search" value="{{ $search ?? '' }}" 
                           placeholder="Search or start new chat..." 
                           class="w-full pl-10 pr-4 py-2 bg-gray-100 rounded-full text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 absolute left-3 top-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </form>
            </div>

            <!-- Suggested Users -->
            @if($users->isNotEmpty())
            <div class="p-3 bg-gray-50 border-b flex-shrink-0">
                <p class="text-xs text-gray-500 font-semibold uppercase mb-2">Suggested</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($users->take(5) as $user)
                    <form action="{{ route('send.message') }}" method="POST" class="inline">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $user->id }}">
                        <input type="hidden" name="message" value="Hello! 👋">
                        <button type="submit" class="text-xs bg-blue-100 text-blue-700 px-3 py-1 rounded-full hover:bg-blue-200 transition">
                            {{ $user->name }}
                        </button>
                    </form>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Chat List -->
            <div class="flex-1 overflow-y-auto" style="scrollbar-width: thin;">
                @forelse($threads as $thread)
                    @php
                        $participant = $thread->participants->first(function($p) {
                            return $p->owner_id !== auth()->id();
                        });
                        $name = $participant?->owner?->name ?? 'Unknown User';
                        $initial = strtoupper(substr($name, 0, 1));
                        $colors = ['#4f46e5', '#3b82f6', '#2563eb', '#1d4ed8', '#6366f1', '#8b5cf6', '#ec4899', '#f59e0b'];
                        $color = $colors[$loop->index % count($colors)];
                        $lastMsg = $thread->latestMessage?->body ?? 'No messages yet';
                        if (str_contains($lastMsg, 'messenger/') || str_contains($lastMsg, 'storage/')) {
                            $lastMsg = '📎 Attachment';
                        }
                        $time = $thread->updated_at?->diffForHumans() ?? '';
                    @endphp
                    <div class="chat-item p-3 border-b hover:bg-gray-50 cursor-pointer flex items-center gap-3 transition duration-150"
                         @click="loadMessages('{{ $thread->id }}', '{{ addslashes($name) }}', '{{ $initial }}', '{{ $color }}'); sidebarOpen = false;"
                         id="chat-{{ $thread->id }}"
                         data-thread-id="{{ $thread->id }}">
                        <div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg flex-shrink-0 shadow-sm" 
                             style="background: {{ $color }};">
                            {{ $initial }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-center">
                                <p class="font-semibold text-sm text-gray-800 truncate">{{ $name }}</p>
                                <span class="text-xs text-gray-400 flex-shrink-0">{{ $time }}</span>
                            </div>
                            <p class="text-xs text-gray-500 truncate">{{ $lastMsg }}</p>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12 text-gray-400">
                        <svg class="w-16 h-16 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                        <p>No conversations yet</p>
                        <p class="text-sm">Start a chat with someone above</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="flex-1 flex flex-col bg-gray-50 h-full relative" id="chatMain">
            
            <!-- Chat Header -->
            <div class="p-4 bg-white border-b shadow-sm flex items-center justify-between flex-shrink-0" id="chatHeader">
                <div class="flex items-center gap-3">
                    <button class="md:hidden text-gray-600 mr-2 p-2 hover:bg-gray-100 rounded-lg" @click="sidebarOpen = !sidebarOpen">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold shadow-sm" 
                         id="chatAvatar" style="background: #4f46e5;">
                        U
                    </div>
                    <div>
                        <h2 class="font-bold text-gray-700" id="chatName">Select a chat</h2>
                        <span class="text-xs text-gray-400" id="chatStatus">Click a conversation to start</span>
                    </div>
                </div>
                <div class="flex items-center gap-1">
                    <button class="p-2 rounded-full hover:bg-gray-100 transition text-gray-500">
                        <!-- <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg> -->
                    </button>
                    <button class="p-2 rounded-full hover:bg-gray-100 transition text-gray-500">
                        <!-- <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg> -->
                    </button>
                    <button class="p-2 rounded-full hover:bg-gray-100 transition text-gray-500">
                        <!-- <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg> -->
                    </button>
                </div>
            </div>

            <!-- Messages Area -->
            <div class="flex-1 overflow-y-auto p-4 space-y-3" id="messagesArea" style="background-image: url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%23d1d5db\' fill-opacity=\'0.15\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');">
                <div class="text-center text-gray-400 py-20" id="emptyState">
                    <svg class="w-16 h-16 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    <p class="text-lg font-medium">Select a chat to start messaging</p>
                </div>
                <div id="messagesContainer" class="space-y-3 hidden"></div>
                <div id="messagesLoading" class="hidden text-center py-10">
                    <svg class="w-8 h-8 mx-auto text-blue-500 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                </div>
            </div>

            <!-- Input Area -->
            <div class="p-3 bg-white border-t flex items-end gap-2 flex-shrink-0" id="inputArea">
                
                <!-- Attachment Button -->
                <button type="button" onclick="document.getElementById('fileInput').click()" 
                        class="p-2.5 rounded-full hover:bg-gray-100 transition text-gray-500 hover:text-blue-600 flex-shrink-0" 
                        title="Attach file">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                </button>
                
                <input type="file" id="fileInput" name="attachments[]" class="hidden" multiple 
                       accept="image/*,.pdf,.doc,.docx,.txt,.zip,.rar,.xls,.xlsx"
                       onchange="handleFileSelect(this)">

                <!-- Message Form -->
                <form id="messageForm" class="flex-1 flex items-end gap-2" onsubmit="event.preventDefault(); sendMessage();">
                    @csrf
                    <input type="hidden" name="thread_id" id="threadIdInput">
                    
                    <div class="flex-1 relative">
                        <!-- File Preview Area -->
                        <div id="filePreviewArea" class="hidden mb-2 flex flex-wrap gap-2"></div>
                        <input type="text" name="message" id="messageInput" 
                               class="w-full border rounded-2xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-100"
                               placeholder="Type a message..." autocomplete="off">
                    </div>
                    
                    <button type="submit" id="sendBtn"
                            class="bg-blue-600 text-white p-2.5 rounded-full font-semibold hover:bg-blue-700 transition flex items-center justify-center w-11 h-11 shadow-sm flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </button>
                </form>

                <button type="button" class="p-2.5 rounded-full hover:bg-gray-100 transition text-gray-500 flex-shrink-0">
                    <!-- <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/></svg> -->
                </button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="toastContainer" class="fixed bottom-20 left-1/2 transform -translate-x-1/2 z-50 pointer-events-none"></div>

    <style>
        .chat-item.active { background: #eff6ff; border-left: 4px solid #3b82f6; }
        .message-sent { background: #3b82f6; color: white; align-self: flex-end; border-bottom-right-radius: 4px; }
        .message-received { background: #ffffff; color: #1f2937; align-self: flex-start; border-bottom-left-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .message-attachment { background: rgba(255,255,255,0.2); border-radius: 8px; padding: 8px 12px; display: inline-flex; align-items: center; gap: 8px; font-size: 13px; margin-top: 6px; cursor: pointer; }
        .message-received .message-attachment { background: #f3f4f6; color: #374151; }
        .message-image { max-width: 240px; max-height: 240px; border-radius: 12px; cursor: pointer; }
        .file-preview-chip { display: inline-flex; align-items: center; gap: 6px; background: #eff6ff; border: 1px solid #bfdbfe; padding: 6px 12px; border-radius: 20px; font-size: 12px; color: #1e40af; }
        .file-preview-chip img { width: 40px; height: 40px; object-fit: cover; border-radius: 6px; }
        .file-preview-chip .remove-file { cursor: pointer; color: #ef4444; margin-left: 4px; }
        @media (max-width: 768px) { #sidebar { position: fixed; inset: 0; z-index: 50; } }
        #messagesArea::-webkit-scrollbar { width: 5px; }
        #messagesArea::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
        .animate-spin { animation: spin 1s linear infinite; }
        @keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
    </style>

    <script>
        let currentThreadId = null;
        let selectedFiles = [];
        let isLoading = false;
        let lastMessageCount = 0;
        let pollInterval = null;

        function handleFileSelect(input) {
            selectedFiles = Array.from(input.files);
            renderFilePreviews();
            if (selectedFiles.length > 0) showToast(`${selectedFiles.length} file(s) selected`);
        }

        function renderFilePreviews() {
            const previewArea = document.getElementById('filePreviewArea');
            previewArea.innerHTML = '';
            if (selectedFiles.length === 0) { previewArea.classList.add('hidden'); return; }
            previewArea.classList.remove('hidden');
            
            selectedFiles.forEach((file, index) => {
                const isImage = file.type.startsWith('image/');
                const chip = document.createElement('div');
                chip.className = 'file-preview-chip';
                if (isImage) {
                    const url = URL.createObjectURL(file);
                    chip.innerHTML = `<img src="${url}" alt=""><span class="truncate max-w-[100px]">${file.name}</span><span onclick="removeFile(${index})" class="remove-file">✕</span>`;
                } else {
                    chip.innerHTML = `<svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg><span class="truncate max-w-[100px]">${file.name}</span><span onclick="removeFile(${index})" class="remove-file">✕</span>`;
                }
                previewArea.appendChild(chip);
            });
        }

        function removeFile(index) {
            selectedFiles.splice(index, 1);
            renderFilePreviews();
            if (selectedFiles.length === 0) document.getElementById('fileInput').value = '';
        }

        function loadMessages(threadId, userName, initial, color) {
            if (isLoading || threadId === currentThreadId) return;
            
            if (pollInterval) clearInterval(pollInterval);
            
            currentThreadId = threadId;
            isLoading = true;
            lastMessageCount = 0;

            document.getElementById('chatName').textContent = userName || 'Unknown User';
            document.getElementById('chatAvatar').textContent = initial || 'U';
            document.getElementById('chatAvatar').style.background = color || '#4f46e5';
            document.getElementById('chatStatus').innerHTML = '<span class="w-2 h-2 bg-green-500 rounded-full inline-block mr-1"></span> Online';
            document.getElementById('threadIdInput').value = threadId;

            document.getElementById('emptyState').classList.add('hidden');
            document.getElementById('messagesContainer').classList.remove('hidden');
            document.getElementById('messagesLoading').classList.remove('hidden');
            document.getElementById('messagesContainer').innerHTML = '';

            document.querySelectorAll('.chat-item').forEach(el => el.classList.remove('active'));
            const activeItem = document.querySelector(`#chat-${threadId}`);
            if (activeItem) activeItem.classList.add('active');

            fetchMessages(threadId, true);
        }

        function fetchMessages(threadId, isInitial = false) {
            fetch(`/messenger/messages/${threadId}`)
                .then(response => response.json())
                .then(data => {
                    if (isInitial) {
                        document.getElementById('messagesLoading').classList.add('hidden');
                        isLoading = false;
                    }
                    
                    if (data.success) {
                        if (data.messages.length !== lastMessageCount) {
                            lastMessageCount = data.messages.length;
                            renderMessages(data.messages);
                            if (!isInitial) markAsRead(threadId);
                        }
                    } else {
                        showError(data.message || 'Failed to load messages');
                    }
                })
                .catch(err => {
                    if (isInitial) {
                        document.getElementById('messagesLoading').classList.add('hidden');
                        isLoading = false;
                    }
                    console.error('Error:', err);
                    if (isInitial) showError('Error loading messages. Please try again.');
                });
        }

function renderMessages(messages) {
    const container = document.getElementById('messagesContainer');
    const userId = '{{ auth()->id() }}';
    
    if (!messages || messages.length === 0) {
        container.innerHTML = `<div class="text-center text-gray-400 py-10"><svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg><p>No messages yet</p><p class="text-xs">Start the conversation!</p></div>`;
        return;
    }

    let html = '';
    messages.forEach(msg => {
        const isSent = msg.is_sender || msg.sender_id == userId;
        const time = msg.time || '';
        
        let attachmentHtml = '';
        let bodyHtml = '';
        
        // Check if message has attachment
        if (msg.attachment) {
            if (msg.attachment.type === 'image') {
                // Show actual image preview
                attachmentHtml = `<div class="mt-1"><img src="${msg.attachment.url}" class="message-image" onclick="window.open('${msg.attachment.url}', '_blank')" alt="Image" style="max-width:200px; max-height:200px; border-radius:8px; cursor:pointer;"></div>`;
            } else {
                // Show document with icon
                const icon = msg.attachment.name.match(/\.pdf$/i) ? 'fa-file-pdf' : 
                            msg.attachment.name.match(/\.(doc|docx)$/i) ? 'fa-file-word' :
                            msg.attachment.name.match(/\.(xls|xlsx)$/i) ? 'fa-file-excel' : 'fa-file';
                attachmentHtml = `<div class="message-attachment" onclick="window.open('${msg.attachment.url}', '_blank')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>${msg.attachment.name}</span>
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                </div>`;
            }
        }

        // Show text message only if body exists (and it's not just an attachment)
        if (msg.body && msg.body.trim() !== '') {
            bodyHtml = `<div>${escapeHtml(msg.body)}</div>`;
        }

        // If only attachment and no text, show appropriate label
        if (!bodyHtml && attachmentHtml) {
            // Don't show extra "Image" text, attachment is enough
        }

        html += `
            <div class="flex flex-col ${isSent ? 'items-end' : 'items-start'} mb-2">
                <div class="${isSent ? 'message-sent' : 'message-received'} max-w-[80%] px-4 py-2.5 rounded-2xl text-sm leading-relaxed">
                    ${bodyHtml}
                    ${attachmentHtml}
                </div>
                <span class="text-[10px] text-gray-400 mt-1 ${isSent ? 'mr-1' : 'ml-1'}">${time}</span>
            </div>
        `;
    });

    container.innerHTML = html;
    scrollToBottom();
    
    if (!pollInterval) {
        pollInterval = setInterval(() => {
            if (currentThreadId && !isLoading) {
                fetchMessages(currentThreadId, false);
            }
        }, 5000);
    }
}

        function sendMessage() {
            const threadId = document.getElementById('threadIdInput').value;
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            const sendBtn = document.getElementById('sendBtn');

            if (!threadId) {
                showToast('Please select a chat first', 'warning');
                return;
            }

            if (!message && selectedFiles.length === 0) return;

            sendBtn.disabled = true;
            sendBtn.innerHTML = '<svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>';

            const formData = new FormData();
            formData.append('thread_id', threadId);
            formData.append('message', message);
            
            selectedFiles.forEach(file => {
                formData.append('attachments[]', file);
            });

            fetch('{{ route("send.message.ajax") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    messageInput.value = '';
                    document.getElementById('fileInput').value = '';
                    selectedFiles = [];
                    document.getElementById('filePreviewArea').innerHTML = '';
                    document.getElementById('filePreviewArea').classList.add('hidden');
                    
                    fetchMessages(threadId, false);
                    showToast('Message sent', 'success');
                } else {
                    showToast(data.message || 'Failed to send message', 'error');
                }
            })
            .catch(err => {
                console.error('Send error:', err);
                showToast('Network error. Please try again.', 'error');
            })
            .finally(() => {
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>';
            });
        }

        function markAsRead(threadId) {
            fetch('{{ route("messenger.mark.read") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ thread_id: threadId })
            }).catch(() => {});
        }

        function scrollToBottom() {
            const area = document.getElementById('messagesArea');
            if (area) area.scrollTop = area.scrollHeight;
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function showToast(msg, type = 'info') {
            const container = document.getElementById('toastContainer');
            const toast = document.createElement('div');
            const colors = { success: 'bg-green-600', error: 'bg-red-600', warning: 'bg-yellow-600', info: 'bg-gray-800' };
            toast.className = `${colors[type] || colors.info} text-white px-5 py-3 rounded-lg shadow-lg mb-2 flex items-center gap-2 pointer-events-auto`;
            toast.innerHTML = `<span>${msg}</span>`;
            container.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = 'opacity 0.3s'; setTimeout(() => toast.remove(), 300); }, 3000);
        }

        function showError(msg) {
            document.getElementById('messagesContainer').innerHTML = `
                <div class="text-center text-red-400 py-10">
                    <svg class="w-12 h-12 mx-auto mb-2 text-red-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p>${msg}</p>
                    <button onclick="retryLoadMessages()" class="mt-3 text-blue-500 hover:underline text-sm">Retry</button>
                </div>
            `;
        }

        function retryLoadMessages() {
            if (currentThreadId) fetchMessages(currentThreadId, true);
        }

        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const firstChat = document.querySelector('.chat-item');
            if (firstChat) setTimeout(() => firstChat.click(), 200);
        });
    </script>
</x-app-layout>