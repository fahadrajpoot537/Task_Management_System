<div class="flex h-screen bg-gray-100 dark:bg-gray-900">
    <!-- Left Column - Channels List -->
    <div class="w-1/4 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 overflow-y-auto">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Channels</h2>
        </div>
        
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @forelse($channels as $channel)
                <div 
                    wire:click="selectChannel({{ $channel['id'] }})"
                    class="p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150
                           {{ $selectedChannel && $selectedChannel['id'] === $channel['id'] ? 'bg-blue-50 dark:bg-blue-900/20 border-l-4 border-blue-500' : '' }}"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">
                                {{ $channel['is_private'] ? '🔒' : '#' }} {{ $channel['name'] }}
                            </h3>
                            @if($channel['description'])
                                <p class="text-sm text-gray-500 dark:text-gray-400 truncate">
                                    {{ Str::limit($channel['description'], 40) }}
                                </p>
                            @endif
                        </div>
                        <div class="text-xs text-gray-400 dark:text-gray-500">
                            {{ count($channel['members']) }} members
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                    <p>No channels available</p>
                    <p class="text-sm mt-2">Join or create a channel to start chatting</p>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Right Column - Messages -->
    <div class="flex-1 flex flex-col">
        @if($selectedChannel)
            <!-- Channel Header -->
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between">
<div>
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $selectedChannel['is_private'] ? '🔒' : '#' }} {{ $selectedChannel['name'] }}
                        </h2>
                        @if($selectedChannel['description'])
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $selectedChannel['description'] }}</p>
                        @endif
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ count($selectedChannel['members']) }} members
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
                    <div class="flex items-start space-x-3 {{ $message['user_id'] === auth()->id() ? 'flex-row-reverse space-x-reverse' : '' }}">
                        <!-- User Avatar -->
                        <div class="flex-shrink-0">
                            @if(isset($message['user']['avatar_url']) && $message['user']['avatar_url'])
                                <img 
                                    src="{{ $message['user']['avatar_url'] }}" 
                                    alt="{{ $message['user']['name'] }}"
                                    class="w-10 h-10 rounded-full"
                                >
                            @else
                                <div class="w-10 h-10 rounded-full bg-blue-500 text-white flex items-center justify-center font-semibold text-sm">
                                    {{ strtoupper(substr($message['user']['name'], 0, 1)) }}
                                </div>
                            @endif
                        </div>
                        
                        <!-- Message Content -->
                        <div class="flex-1 {{ $message['user_id'] === auth()->id() ? 'text-right' : '' }}">
                            <div class="flex items-baseline {{ $message['user_id'] === auth()->id() ? 'justify-end' : '' }} space-x-2">
                                <span class="font-semibold text-gray-900 dark:text-white text-sm">
                                    {{ $message['user']['name'] }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ \Carbon\Carbon::parse($message['created_at'])->diffForHumans() }}
                                </span>
                            </div>
                            <div class="mt-1">
                                <div class="inline-block px-4 py-2 rounded-lg {{ $message['user_id'] === auth()->id() ? 'bg-blue-500 text-white' : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white' }}">
                                    {{ $message['body'] }}
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
                            <p class="text-sm mt-1">Be the first to send a message!</p>
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
                        placeholder="Type a message..."
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
                @error('newMessage')
                    <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        @else
            <!-- No Channel Selected -->
            <div class="flex-1 flex items-center justify-center bg-gray-50 dark:bg-gray-900">
                <div class="text-center text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    <p class="mt-4 text-lg font-semibold">Welcome to Chat</p>
                    <p class="mt-2">Select a channel from the left to start messaging</p>
                </div>
            </div>
        @endif
    </div>
</div>
