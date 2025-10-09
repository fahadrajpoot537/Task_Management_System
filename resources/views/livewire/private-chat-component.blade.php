<div class="flex h-screen bg-gray-100 dark:bg-gray-900">
    <!-- Left Column - Conversations List -->
    <div class="w-1/3 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 overflow-y-auto">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Direct Messages</h2>
        </div>
        
        <!-- Start New Conversation -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Start New Conversation</h3>
            <div class="space-y-2">
                @forelse($users as $user)
                    <div 
                        wire:click="selectUser({{ $user['id'] }})"
                        class="flex items-center p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors duration-150"
                    >
                        @if($user['avatar'])
                            <img src="{{ Storage::url($user['avatar']) }}" alt="{{ $user['name'] }}" class="w-8 h-8 rounded-full mr-3">
                        @else
                            <div class="w-8 h-8 rounded-full bg-blue-500 text-white flex items-center justify-center font-semibold text-sm mr-3">
                                {{ strtoupper(substr($user['name'], 0, 1)) }}
                            </div>
                        @endif
                        <div>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $user['name'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user['email'] }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No other users available</p>
                @endforelse
            </div>
        </div>

        <!-- Existing Conversations -->
        <div class="p-4">
            <h3 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">Recent Conversations</h3>
            <div class="space-y-2">
                @forelse($conversations as $conversation)
                    <div 
                        wire:click="selectUser({{ $conversation['user']['id'] }})"
                        class="flex items-center p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors duration-150
                               {{ $selectedUser && $selectedUser['id'] === $conversation['user']['id'] ? 'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500' : '' }}"
                    >
                        @if($conversation['user']['avatar'])
                            <img src="{{ Storage::url($conversation['user']['avatar']) }}" alt="{{ $conversation['user']['name'] }}" class="w-10 h-10 rounded-full mr-3">
                        @else
                            <div class="w-10 h-10 rounded-full bg-blue-500 text-white flex items-center justify-center font-semibold text-sm mr-3">
                                {{ strtoupper(substr($conversation['user']['name'], 0, 1)) }}
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <p class="font-medium text-gray-900 dark:text-white truncate">{{ $conversation['user']['name'] }}</p>
                                @if($conversation['unread_count'] > 0)
                                    <span class="bg-red-500 text-white text-xs rounded-full px-2 py-1 min-w-[20px] text-center">
                                        {{ $conversation['unread_count'] }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                {{ Str::limit($conversation['last_message']['message'], 50) }}
                            </p>
                            <p class="text-xs text-gray-400 dark:text-gray-500">
                                {{ \Carbon\Carbon::parse($conversation['last_message']['created_at'])->diffForHumans() }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 dark:text-gray-400">No conversations yet</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Right Column - Messages -->
    <div class="flex-1 flex flex-col">
        @if($selectedUser)
            <!-- User Header -->
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center">
                    @if($selectedUser['avatar'])
                        <img src="{{ Storage::url($selectedUser['avatar']) }}" alt="{{ $selectedUser['name'] }}" class="w-10 h-10 rounded-full mr-3">
                    @else
                        <div class="w-10 h-10 rounded-full bg-blue-500 text-white flex items-center justify-center font-semibold text-sm mr-3">
                            {{ strtoupper(substr($selectedUser['name'], 0, 1)) }}
                        </div>
                    @endif
                    <div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $selectedUser['name'] }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $selectedUser['email'] }}</p>
                    </div>
                </div>
            </div>

            <!-- Messages Area -->
            <div 
                id="message-container" 
                class="flex-1 overflow-y-auto p-4 space-y-4 bg-gray-50 dark:bg-gray-900"
                x-data="{ scrollToBottom() { this.$el.scrollTop = this.$el.scrollHeight; } }"
                x-init="scrollToBottom()"
                @message-sent.window="setTimeout(() => scrollToBottom(), 100)"
                @message-received.window="setTimeout(() => scrollToBottom(), 100)"
            >
                @forelse($messages ?? [] as $message)
                    <div class="flex items-start space-x-3 {{ $message['sender_id'] === auth()->id() ? 'flex-row-reverse space-x-reverse' : '' }}">
                        <!-- User Avatar -->
                        <div class="flex-shrink-0">
                            @if(isset($message['sender']['avatar_url']) && $message['sender']['avatar_url'])
                                <img 
                                    src="{{ $message['sender']['avatar_url'] }}" 
                                    alt="{{ $message['sender']['name'] }}"
                                    class="w-10 h-10 rounded-full"
                                >
                            @else
                                <div class="w-10 h-10 rounded-full bg-blue-500 text-white flex items-center justify-center font-semibold text-sm">
                                    {{ strtoupper(substr($message['sender']['name'], 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        
                        <!-- Message Content -->
                        <div class="flex-1 {{ $message['sender_id'] === auth()->id() ? 'text-right' : '' }}">
                            <div class="flex items-baseline {{ $message['sender_id'] === auth()->id() ? 'justify-end' : '' }} space-x-2">
                                <span class="font-semibold text-gray-900 dark:text-white text-sm">
                                    {{ $message['sender']['name'] }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($message['created_at'])->diffForHumans() }}
                                </span>
                            </div>
                            <div class="mt-1">
                                <div class="inline-block px-4 py-2 rounded-lg {{ $message['sender_id'] === auth()->id() ? 'bg-blue-500 text-white' : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white' }}">
                                    {{ $message['message'] }}
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="flex items-center justify-center h-full">
                        <div class="text-center text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            <p class="mt-2">No messages yet</p>
                            <p class="text-sm mt-1">Start the conversation!</p>
                        </div>
                    </div>
                @endforelse
            </div>

            <!-- Message Input -->
            <div class="bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700 p-4">
                <form wire:submit.prevent="sendMessage" class="flex space-x-3">
                    <input 
                        type="text" 
                        wire:model.live="newMessage"
                        placeholder="Type a private message..."
                        class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                        autocomplete="off"
                    >
                    <button 
                        type="submit"
                        class="px-6 py-2 bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-lg transition-colors duration-150 disabled:opacity-50 disabled:cursor-not-allowed"
                        @disabled(!$newMessage)
                    >
                        Send
                    </button>
                </form>
            </div>
        @else
            <!-- No User Selected -->
            <div class="flex-1 flex items-center justify-center bg-gray-50 dark:bg-gray-900">
                <div class="text-center text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    <p class="mt-4 text-lg font-semibold">Private Messages</p>
                    <p class="mt-2">Select a user to start a private conversation</p>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Listen for direct message events
    if (window.Echo) {
        const userId = {{ auth()->id() }};
        
        window.Echo.private(`user.${userId}`)
            .listen('DirectMessageSent', (e) => {
                console.log('Direct message received:', e);
                
                // Reload the Livewire component to show new messages
                Livewire.dispatch('refresh-component');
            });
    }
});
</script>
