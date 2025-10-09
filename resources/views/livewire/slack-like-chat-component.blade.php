<div class="chat-container" wire:poll.5s="refreshComponent">
    <!-- Enhanced Contacts Sidebar -->
    <div class="contacts-sidebar {{ $selectedUser ? 'chat-open' : '' }}">
        <!-- Premium Header -->
        <div class="contacts-header">
            <div class="header-content">
                <div class="header-top">
                    <h2>Messages</h2>
                </div>
                <!-- Header Action Buttons -->
                <div class="header-action-buttons" wire:ignore>
                    <button class="header-btn active" id="recentHeaderBtn" data-view="recent">
                        <i class="fas fa-clock"></i>
                        <span>Recent</span>
                    </button>
                    <button class="header-btn" id="allHeaderBtn" data-view="all">
                        <i class="fas fa-users"></i>
                        <span>All</span>
                    </button>
                    <button class="header-btn" id="searchHeaderBtn" data-view="search">
                        <i class="fas fa-search"></i>
                        <span>Search</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Premium Search -->
        <div class="search-container" id="searchContainer" style="display: none;" wire:ignore>
            <div class="search-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" placeholder="Search contacts..." class="search-input" id="contactSearch">
                <button class="search-clear" id="searchClear" style="display: none;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>

        <!-- Recent Conversations (First Priority) -->
        <div class="recent-section" id="recentSection" style="{{ count($conversations) > 0 ? 'display: block;' : 'display: none;' }}">
                {{-- <div class="section-header">
                    <h3 class="section-title">
                        <i class="fas fa-clock"></i>
                    </h3>
                    <div class="section-actions">
                        <button class="section-btn" title="Clear recent">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div> --}}
                @if (count($conversations) > 0)
                    <div class="recent-list">
                        @foreach ($conversations as $conversation)
                        <div class="contact-item recent-item {{ $selectedUser && $selectedUser['id'] == $conversation['user']['id'] ? 'active' : '' }}"
                            wire:click="selectUser({{ $conversation['user']['id'] }})">
                            <div class="contact-avatar-wrapper">
                                <div class="contact-avatar">
                                    @if (isset($conversation['user']['avatar']) && $conversation['user']['avatar'])
                                        <img src="{{ Storage::url($conversation['user']['avatar']) }}"
                                            alt="{{ $conversation['user']['name'] }}" class="avatar-img">
                                    @else
                                        <div class="avatar-placeholder">
                                            {{ strtoupper(substr($conversation['user']['name'], 0, 1)) }}
                                        </div>
                                    @endif
                                </div>
                                @if (isset($conversation['unread_count']) && $conversation['unread_count'] > 0)
                                    <div class="unread-count-badge">{{ $conversation['unread_count'] }}</div>
                                @endif
                            </div>
                            <div class="contact-details">
                                <div class="contact-main-info">
                                    <div class="contact-name">{{ $conversation['user']['name'] }}</div>
                                    <div class="last-message-preview">
                                        {{ Str::limit($conversation['last_message']['message'], 30) }}</div>
                                </div>
                                <div class="message-meta">
                                    <div class="message-time">
                                        {{ \Carbon\Carbon::parse($conversation['last_message']['created_at'])->diffForHumans() }}
                                    </div>
                                    @if (isset($conversation['unread_count']) && $conversation['unread_count'] > 0)
                                        <div class="unread-indicator"></div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @endif
            </div>

        <!-- All Contacts (Below Recent) -->
        <div class="users-section" id="allContactsSection">
            {{-- <div class="section-header">
                <h3 class="section-title">
                    <i class="fas fa-users"></i>
                </h3>
                <div class="section-actions">
                    <button class="section-btn" title="Sort by name">
                        <i class="fas fa-sort-alpha-down"></i>
                    </button>
                    <button class="section-btn" title="Filter online">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
            </div> --}}
            <div class="users-list" id="usersList">
                @forelse($users as $user)
                    <div class="contact-item {{ $selectedUser && $selectedUser['id'] == $user['id'] ? 'active' : '' }}"
                        wire:click="selectUser({{ $user['id'] }})" data-name="{{ strtolower($user['name']) }}"
                        data-email="{{ strtolower($user['email']) }}"
                        data-online="{{ isset($user['is_online']) && $user['is_online'] ? 'true' : 'false' }}">
                        <div class="contact-avatar-wrapper">
                            <div class="contact-avatar">
                                @if (isset($user['avatar']) && $user['avatar'])
                                    <img src="{{ Storage::url($user['avatar']) }}" alt="{{ $user['name'] }}"
                                        class="avatar-img">
                                @else
                                    <div class="avatar-placeholder">
                                        {{ strtoupper(substr($user['name'], 0, 1)) }}
                                    </div>
                                @endif
                            </div>
                            @if (isset($user['is_online']) && $user['is_online'])
                                <div class="online-status-dot"></div>
                            @endif
                        </div>
                        <div class="contact-details">
                            <div class="contact-main-info">
                                <div class="contact-name">{{ $user['name'] }}</div>
                                <div class="contact-email">{{ $user['email'] }}</div>
                            </div>
                            <div class="contact-status">
                                @if (isset($user['is_online']) && $user['is_online'])
                                    <div class="status-badge online">
                                        <div class="status-dot"></div>
                                        <span>Online</span>
                                    </div>
                                @else
                                    <div class="status-badge offline">
                                        <span>{{ isset($user['online_status']) ? $user['online_status'] : 'Offline' }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="contact-actions">
                            <button class="contact-action-btn" title="More options">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="no-contacts-state">
                        <div class="no-contacts-icon">
                            <i class="fas fa-user-friends"></i>
                        </div>
                        <h4>No contacts found</h4>
                        <p>Start by adding some contacts to begin chatting</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Chat Area -->
    <!-- Enhanced Chat Interface -->
    <div class="chat-area" style="{{ $selectedUser ? 'display: flex;' : 'display: none;' }}">
        @if ($selectedUser)
            <!-- Enhanced Contact Bar -->
            <div class="chat-header">
                <div class="chat-user-info">
                    <div class="chat-avatar">
                        @if (isset($selectedUser['avatar']) && $selectedUser['avatar'])
                            <img src="{{ Storage::url($selectedUser['avatar']) }}"
                                alt="{{ $selectedUser['name'] ?? 'User' }}" class="avatar-img">
                        @else
                            <div class="avatar-placeholder">
                                {{ strtoupper(substr($selectedUser['name'] ?? 'U', 0, 1)) }}
                            </div>
                        @endif
                        @if (isset($selectedUser['is_online']) && $selectedUser['is_online'])
                            <div class="online-indicator"></div>
                        @endif
                    </div>
                    <div class="user-details">
                        <h3 class="user-name">{{ $selectedUser['name'] ?? 'Select a User' }}</h3>
                        <div class="user-status">
                            @if (isset($selectedUser['is_online']) && $selectedUser['is_online'])
                                <div class="status-online">
                                    <div class="online-dot"></div>
                                    <span>Online now</span>
                                </div>
                            @else
                                <span class="status-offline">Last seen
                                    {{ isset($selectedUser['online_status']) ? $selectedUser['online_status'] : 'recently' }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="chat-actions">
                    <button wire:click="refreshComponent" class="action-btn" title="Refresh Messages">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button class="action-btn" title="Voice Call">
                        <i class="fas fa-phone"></i>
                    </button>
                    <button class="action-btn" title="Video Call">
                        <i class="fas fa-video"></i>
                    </button>
                    <button class="action-btn" title="More Options">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                </div>
            </div>

            <!-- Enhanced Messages -->
            <div id="chat" class="messages">
                @forelse($messages ?? [] as $message)
                    @if (
                        $loop->first ||
                            \Carbon\Carbon::parse($message['created_at'])->format('Y-m-d') !==
                                \Carbon\Carbon::parse($messages[$loop->index - 1]['created_at'])->format('Y-m-d'))
                        <div class="time-divider">
                            <span>{{ \Carbon\Carbon::parse($message['created_at'])->format('F j, Y') }}</span>
                        </div>
                    @endif

                    <div
                        class="message-wrapper {{ $message['sender_id'] === auth()->id() ? 'own-message' : 'other-message' }}">
                        <div class="message {{ $message['sender_id'] === auth()->id() ? 'parker' : 'stark' }}">
                            <div class="message-content">{{ $message['message'] }}</div>
                            <div class="message-meta">
                                <span
                                    class="message-time">{{ \Carbon\Carbon::parse($message['created_at'])->format('h:i A') }}</span>
                                @if ($message['sender_id'] === auth()->id())
                                    <div class="message-status">
                                        <i class="fas fa-check-double"></i>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-chat">
                        <div class="empty-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3>Start your conversation</h3>
                        <p>Send your first message to {{ $selectedUser['name'] ?? 'this user' }}</p>
                    </div>
                @endforelse

                <!-- Typing Indicator -->
                <div class="typing-indicator" id="typingIndicator" style="display: none;">
                    <div class="typing-dots">
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                        <div class="typing-dot"></div>
                    </div>
                    <span class="typing-text">{{ $selectedUser['name'] ?? 'User' }} typing</span>
                </div>
            </div>

            <!-- Enhanced Input Area with Emoji Picker -->
            <div class="input">
                <div class="input-field">
                    <button type="button" class="emoji-btn" id="emoji-button" title="Add emoji"
                        onclick="toggleEmojiPicker()">
                        😀
                    </button>
                    <input type="text" wire:model.live="newMessage" placeholder="Type a message..."
                        class="message-input" id="message-input"
                        @keydown.enter.prevent="if (!event.shiftKey) $wire.sendMessage()"
                        @input="handleRealTimeInput()">
                    <button class="send-btn" wire:click="sendMessage" @disabled(!$newMessage)>
                        <i class="fas fa-paper-plane"></i>
                        <span class="emoji-counter" id="emoji-counter" style="display: none;"></span>
                    </button>
                </div>

                <!-- Emoji Picker Panel -->
                <!-- Replace your current emoji picker section with this simplified version -->
                <div class="emoji-picker" id="emoji-picker" wire:ignore>
                    <div class="emoji-picker-header">
                        <div class="emoji-categories">
                            <button class="emoji-category-btn active" data-category="smileys"
                                onclick="switchCategory('smileys')">😀</button>
                            <button class="emoji-category-btn" data-category="hearts"
                                onclick="switchCategory('hearts')">❤️</button>
                            <button class="emoji-category-btn" data-category="hands"
                                onclick="switchCategory('hands')">👍</button>
                            <button class="emoji-category-btn" data-category="symbols"
                                onclick="switchCategory('symbols')">💯</button>
                        </div>
                    </div>
                    <div class="emoji-picker-content">
                        <div class="emoji-grid" id="emoji-grid">
                            <!-- Emojis will be loaded here by JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Update your emoji button -->

            </div>
        </div>
        @endif

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* Enhanced scrolling */
        .messages {
            scroll-behavior: smooth;
            overflow-anchor: auto;
        }

        /* Ensure messages container has proper height */
        .messages {
            height: calc(100vh - 180px);
            overflow-y: auto;
            overflow-x: hidden;
        }

        /* Auto-scroll anchor */
        .messages::after {
            content: '';
            display: block;
            height: 1px;
            width: 100%;
        }

        /* Reset main content styling */
        .main-content {
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
            height: 100% !important;
            max-width: none !important;
            max-height: none !important;
        }

        /* CSS Variables for Theme Switching */
        :root {
            /* Light Theme Variables */
            --chat-bg-primary: #ffffff;
            --chat-bg-secondary: #f8fafc;
            --chat-bg-tertiary: #f1f5f9;
            --chat-text-primary: #111827;
            --chat-text-secondary: #6b7280;
            --chat-text-tertiary: #9ca3af;
            --chat-border: #e5e7eb;
            --chat-border-light: #f3f4f6;
            --chat-shadow: rgba(0, 0, 0, 0.08);
            --chat-shadow-light: rgba(0, 0, 0, 0.05);
            --chat-input-bg: #ffffff;
            --chat-input-border: #e5e7eb;
            --chat-hover-bg: #f3f4f6;
            --chat-active-bg: var(--chat-gradient);
            --chat-message-bg: #ffffff;
            --chat-message-bg-own: var(--chat-gradient);
            --chat-message-text: #111827;
            --chat-message-text-own: #ffffff;

            /* Theme Gradient Variable */
            --chat-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        /* Dark Theme Variables */
        [data-theme="dark"] {
            --chat-bg-primary: #1f2937;
            --chat-bg-secondary: #111827;
            --chat-bg-tertiary: #374151;
            --chat-text-primary: #f9fafb;
            --chat-text-secondary: #d1d5db;
            --chat-text-tertiary: #9ca3af;
            --chat-border: #374151;
            --chat-border-light: #4b5563;
            --chat-shadow: rgba(0, 0, 0, 0.3);
            --chat-shadow-light: rgba(0, 0, 0, 0.2);
            --chat-input-bg: #374151;
            --chat-input-border: #4b5563;
            --chat-hover-bg: #374151;
            --chat-active-bg: var(--chat-gradient);
            --chat-message-bg: #374151;
            --chat-message-bg-own: var(--chat-gradient);
            --chat-message-text: #f9fafb;
            --chat-message-text-own: #ffffff;

            /* Dark Theme Gradient Variable */
            --chat-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        }

        /* Auto-detect dark theme from system */
        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) {
                --chat-bg-primary: #1f2937;
                --chat-bg-secondary: #111827;
                --chat-bg-tertiary: #374151;
                --chat-text-primary: #f9fafb;
                --chat-text-secondary: #d1d5db;
                --chat-text-tertiary: #9ca3af;
                --chat-border: #374151;
                --chat-border-light: #4b5563;
                --chat-shadow: rgba(0, 0, 0, 0.3);
                --chat-shadow-light: rgba(0, 0, 0, 0.2);
                --chat-input-bg: #374151;
                --chat-input-border: #4b5563;
                --chat-hover-bg: #374151;
                --chat-active-bg: var(--chat-gradient);
                --chat-message-bg: #374151;
                --chat-message-bg-own: var(--chat-gradient);
                --chat-message-text: #f9fafb;
                --chat-message-text-own: #ffffff;

                /* Auto-detect Dark Theme Gradient Variable */
                --chat-gradient: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            }
        }

        .chat-container {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            height: calc(100vh - 60px);
            width: calc(100% - 80px);
            margin-left: 80px;
            margin-top: 82px;
            display: flex;
            background: var(--chat-gradient);
            overflow: hidden;
            padding: 0;
            position: fixed;
            top: 0;
            left: 0;
            box-sizing: border-box;
            z-index: 1000;
        }

        /* Prevent horizontal scrolling globally */
        .chat-container * {
            /* max-width: 100%; */
            box-sizing: border-box;
        }

        /* Ensure no container constraints */
        .container,
        .container-fluid,
        .row,
        .col,
        .col-12 {
            padding: 0 !important;
            margin: 0 !important;
            max-width: none !important;
        }

        /* Enhanced Contacts Sidebar */
        .contacts-sidebar {
            width: 380px;
            height: calc(100vh - 60px);
            background: var(--chat-bg-primary, #ffffff);
            border-right: 1px solid var(--chat-border, #e5e7eb);
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            box-shadow: 0 0 30px var(--chat-shadow, rgba(0, 0, 0, 0.08));
            position: relative;
            overflow: hidden;
            flex-shrink: 0;
        }

        /* Contact View Toggle Buttons */
        .contact-view-toggle {
            display: flex;
            margin: 12px 16px;
            background: var(--chat-bg-secondary, #f8fafc);
            border-radius: 8px;
            padding: 4px;
            border: 1px solid var(--chat-border, #e5e7eb);
        }

        .toggle-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 12px;
            border: none;
            background: transparent;
            color: var(--chat-text-secondary, #6b7280);
            font-size: 13px;
            font-weight: 500;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .toggle-btn:hover {
            background: var(--chat-bg-hover, #f1f5f9);
            color: var(--chat-text-primary, #1f2937);
        }

        .toggle-btn.active {
            background: var(--chat-primary, #3b82f6);
            color: white;
            box-shadow: 0 2px 4px rgba(59, 130, 246, 0.2);
        }

        .toggle-btn i {
            font-size: 12px;
        }

        .toggle-btn span {
            font-size: 12px;
            font-weight: 500;
        }

        /* Premium Header */
        .contacts-header {
            padding: 16px 16px 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: var(--chat-gradient);
            color: white;
            position: relative;
        }

        .contacts-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            pointer-events: none;
        }

        .header-content {
            position: relative;
            z-index: 1;
        }

        .header-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .header-content h2 {
            font-size: 20px;
            font-weight: 600;
            margin: 0;
            color: white;
        }

        /* Header Action Buttons */
        .header-action-buttons {
            display: flex;
            gap: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 4px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .header-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 12px;
            border: none;
            background: transparent;
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
            font-weight: 500;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .header-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .header-btn.active {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .header-btn i {
            font-size: 12px;
        }

        .header-btn span {
            font-size: 12px;
            font-weight: 500;
        }

        .new-chat-btn {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
            padding: 10px;
            border-radius: 50%;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }

        .new-chat-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        .online-stats {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .online-indicator-large {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: rgba(255, 255, 255, 0.95);
        }

        .online-dot-large {
            width: 10px;
            height: 10px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse 2s infinite;
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.4);
        }

        .total-contacts {
            font-size: 12px;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
                transform: scale(1);
            }

            50% {
                opacity: 0.7;
                transform: scale(1.1);
            }
        }

        /* Premium Search */
        .search-container {
            padding: 12px 16px 8px;
            background: var(--chat-bg-secondary);
            border-bottom: 1px solid var(--chat-border);
        }

        .search-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            color: #9ca3af;
            font-size: 14px;
            z-index: 2;
        }

        .search-input {
            width: 100%;
            padding: 8px 12px 8px 36px;
            border: 1px solid var(--chat-input-border);
            border-radius: 12px;
            background: var(--chat-input-bg);
            color: var(--chat-text-primary);
            font-size: 14px;
            outline: none;
            transition: all 0.3s ease;
            box-shadow: 0 1px 2px var(--chat-shadow-light);
        }

        .search-input:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1), 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .search-clear {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            color: #9ca3af;
            font-size: 14px;
            cursor: pointer;
            padding: 4px;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .search-clear:hover {
            background: #f3f4f6;
            color: #6b7280;
        }

        /* Enhanced Sections */
        .users-section,
        .recent-section {
            padding: 0;
            background: var(--chat-bg-primary) !important;
        }

        .recent-section {
            flex: 0 0 auto;
            /* max-height: 40%; */
            border-bottom: 2px solid var(--chat-border);
            overflow-y: auto;
            overflow-x: hidden;
            background: var(--chat-bg-primary) !important;
            display: block;
            /* Default visible */
        }

        .users-section {
            flex: 1;
            min-height: 0;
            overflow-y: auto;
            overflow-x: hidden;
            background: var(--chat-bg-primary) !important;
            display: none;
            /* Default hidden */
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 24px 8px;
            background: var(--chat-bg-secondary) !important;
            border-bottom: 1px solid var(--chat-border) !important;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .recent-section .section-header {
            background: var(--chat-bg-secondary) !important;
            border-bottom: 2px solid var(--chat-border) !important;
        }

        .section-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--chat-text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            font-size: 12px;
            opacity: 0.7;
        }

        .section-actions {
            display: flex;
            gap: 4px;
        }

        .section-btn {
            background: none;
            border: none;
            color: #9ca3af;
            font-size: 12px;
            cursor: pointer;
            padding: 6px;
            border-radius: 6px;
            transition: all 0.2s ease;
        }

        .section-btn:hover {
            background: #e5e7eb;
            color: #6b7280;
        }

        /* Enhanced Contact Items */
        .contact-item {
            display: flex;
            align-items: center;
            padding: 10px 16px;
            margin: 0;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            border-bottom: 1px solid #f3f4f6;
        }

        .contact-item:hover {
            background: var(--chat-hover-bg);
            transform: translateX(4px);
            box-shadow: 0 2px 8px var(--chat-shadow-light);
        }

        .contact-item.active {
            background: var(--chat-gradient);
            color: white;
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
            transform: translateX(8px);
        }

        .contact-item.active .contact-name,
        .contact-item.active .contact-email,
        .contact-item.active .last-message-preview,
        .contact-item.active .message-time {
            color: white;
        }

        .contact-item.active .status-badge.offline {
            color: rgba(255, 255, 255, 0.8);
        }

        /* Recent Conversation Items */
        .contact-item.recent-item {
            background: linear-gradient(90deg, var(--chat-bg-primary) 0%, var(--chat-bg-secondary) 100%);
            border-left: 3px solid var(--chat-accent) !important;
            margin-left: 0;
            padding-left: 21px;
        }

        .contact-item.recent-item:hover {
            background: linear-gradient(90deg, var(--chat-bg-secondary) 0%, var(--chat-hover-bg) 100%) !important;
            border-left-color: var(--chat-primary) !important;
        }

        .contact-item.recent-item.active {
            background: var(--chat-gradient);
            border-left-color: rgba(255, 255, 255, 0.3);
        }

        .contact-avatar-wrapper {
            position: relative;
            margin-right: 16px;
        }

        .contact-avatar {
            position: relative;
        }

        .avatar-img,
        .avatar-placeholder {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #f3f4f6;
            transition: all 0.3s ease;
        }

        .avatar-placeholder {
            background: var(--chat-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 20px;
        }

        .contact-item:hover .avatar-img,
        .contact-item:hover .avatar-placeholder {
            border-color: #667eea;
            transform: scale(1.05);
        }

        .contact-item.active .avatar-img,
        .contact-item.active .avatar-placeholder {
            border-color: rgba(255, 255, 255, 0.3);
        }

        .online-status-dot {
            position: absolute;
            bottom: 2px;
            right: 2px;
            width: 16px;
            height: 16px;
            background: #10b981;
            border: 3px solid white;
            border-radius: 50%;
            animation: pulse 2s infinite;
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.4);
        }

        .unread-count-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: #ef4444;
            color: white;
            font-size: 11px;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 12px;
            min-width: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
        }

        .contact-details {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .contact-main-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .contact-name {
            font-weight: 600;
            font-size: 16px;
            color: var(--chat-text-primary);
            line-height: 1.2;
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        .contact-email,
        .last-message-preview {
            font-size: 13px;
            color: var(--chat-text-secondary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.3;
        }

        .contact-status {
            margin-top: 4px;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 500;
            padding: 2px 8px;
            border-radius: 12px;
            background: rgba(0, 0, 0, 0.05);
        }

        .status-badge.online {
            color: #10b981;
            background: rgba(16, 185, 129, 0.1);
        }

        .status-badge.offline {
            color: #9ca3af;
            background: rgba(156, 163, 175, 0.1);
        }

        .status-dot {
            width: 6px;
            height: 6px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .message-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 4px;
        }

        .message-time {
            font-size: 11px;
            color: #9ca3af;
            font-weight: 500;
        }

        .unread-indicator {
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
            box-shadow: 0 0 4px rgba(239, 68, 68, 0.4);
        }

        .contact-actions {
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .contact-item:hover .contact-actions {
            opacity: 1;
        }

        .contact-action-btn {
            background: none;
            border: none;
            color: #9ca3af;
            font-size: 14px;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .contact-action-btn:hover {
            background: #f3f4f6;
            color: #6b7280;
        }

        /* Enhanced Empty State */
        .no-contacts-state {
            text-align: center;
            padding: 60px 24px;
            color: #6b7280;
        }

        .no-contacts-icon {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.3;
            color: #9ca3af;
        }

        /* No Results Message */
        .no-results-message {
            display: none;
            padding: 40px 20px;
            text-align: center;
        }

        .no-results-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .no-results-content i {
            font-size: 48px;
            color: var(--chat-text-tertiary);
            opacity: 0.5;
        }

        .no-results-content h3 {
            font-size: 18px;
            font-weight: 600;
            color: var(--chat-text-secondary);
            margin: 0;
        }

        .no-results-content p {
            font-size: 14px;
            color: var(--chat-text-tertiary);
            margin: 0;
        }

        .no-contacts-state h4 {
            font-size: 18px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 8px;
        }

        .no-contacts-state p {
            font-size: 14px;
            line-height: 1.5;
            margin: 0;
        }

        /* Chat Area */
        .chat-area {
            flex: 1;
            height: calc(100vh - 60px);
            display: flex;
            flex-direction: column;
            background: var(--chat-bg-primary);
            position: relative;
            overflow: hidden;
        }

        .chat-header {
            padding: 20px 24px;
            border-bottom: 1px solid var(--chat-border);
            background: var(--chat-bg-primary);
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 1px 3px var(--chat-shadow-light);
        }

        .chat-user-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .chat-avatar {
            position: relative;
        }

        .chat-avatar .avatar-img,
        .chat-avatar .avatar-placeholder {
            width: 48px;
            height: 48px;
        }

        .user-details {
            flex: 1;
        }

        .user-name {
            font-size: 18px;
            font-weight: 600;
            color: var(--chat-text-primary);
            margin-bottom: 4px;
        }

        .user-status {
            font-size: 14px;
        }

        .status-online {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #10b981;
            font-weight: 500;
        }

        .status-offline {
            color: #6b7280;
        }

        .chat-actions {
            display: flex;
            gap: 8px;
        }

        .action-btn {
            background: none;
            border: none;
            color: #6b7280;
            font-size: 18px;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .action-btn:hover {
            background: #f3f4f6;
            color: #374151;
        }

        .action-btn.loading {
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        /* Messages */
        .messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            overflow-x: hidden;
            background: var(--chat-bg-secondary);
            height: calc(100vh - 180px);
        }

        .time-divider {
            text-align: center;
            margin: 24px 0;
        }

        .time-divider span {
            background: #e5e7eb;
            color: #6b7280;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 500;
        }

        .message-wrapper {
            margin: 16px 0;
            position: relative;
        }


        .message {
            padding: 12px 16px;
            border-radius: 18px;
            max-width: 70%;
            position: relative;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .message.stark {
            background: var(--chat-message-bg);
            color: var(--chat-message-text);
            border-bottom-left-radius: 4px;
        }

        .message.parker {
            background: var(--chat-message-bg-own);
            color: var(--chat-message-text-own);
            border-bottom-right-radius: 4px;
            margin-left: auto;
        }

        .message-content {
            margin-bottom: 4px;
            line-height: 1.4;
            word-wrap: break-word;
            word-break: break-word;
            overflow-wrap: break-word;
            max-width: 100%;
        }

        .message-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11px;
            opacity: 0.7;
        }

        .message-status {
            color: #10b981;
        }

        .empty-chat {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            color: #6b7280;
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 16px;
            opacity: 0.3;
        }

        .empty-chat h3 {
            font-size: 20px;
            color: #374151;
            margin-bottom: 8px;
        }

        .empty-chat p {
            font-size: 14px;
        }

        /* Typing Indicator */
        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 16px;
            color: #6b7280;
            font-style: italic;
        }

        .typing-dots {
            display: flex;
            gap: 4px;
        }

        .typing-dot {
            width: 6px;
            height: 6px;
            background: #9ca3af;
            border-radius: 50%;
            animation: typing 1.4s infinite ease-in-out;
        }

        .typing-dot:nth-child(1) {
            animation-delay: -0.32s;
        }

        .typing-dot:nth-child(2) {
            animation-delay: -0.16s;
        }

        @keyframes typing {

            0%,
            80%,
            100% {
                transform: scale(0.8);
                opacity: 0.5;
            }

            40% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Minimal Input Area */
        .input {
            padding: 12px 20px;
            background: var(--chat-bg-primary);
            border-top: 1px solid var(--chat-border);
            display: flex;
            align-items: center;
            flex-shrink: 0;
            position: relative;
        }


        .input-field {
            flex: 1;
            display: flex;
            align-items: center;
            background: var(--chat-bg-secondary);
            border: 1px solid var(--chat-input-border);
            border-radius: 20px;
            padding: 2px;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px var(--chat-shadow-light);
        }

        .input-field:focus-within {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .message-input {
            flex: 1;
            border: none;
            outline: none;
            padding: 10px 16px;
            font-size: 14px;
            background: transparent;
            color: var(--chat-text-primary);
            font-weight: 400;
        }

        .message-input::placeholder {
            color: var(--chat-text-tertiary);
            font-weight: 400;
        }

        .send-btn {
            background: var(--chat-gradient);
            border: none;
            color: white;
            font-size: 14px;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.2s ease;
            margin-left: 4px;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .send-btn:hover:not(:disabled) {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .send-btn:disabled {
            background: var(--chat-text-tertiary);
            cursor: not-allowed;
            transform: none;
            opacity: 0.5;
        }

        .emoji-counter {
            background: #ff6b6b;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            font-size: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-left: 4px;
            position: absolute;
            top: -5px;
            right: -5px;
        }

        /* Emoji Picker Styles */
        .emoji-btn {
            background: var(--chat-hover-bg);
            border: 1px solid var(--chat-border);
            color: var(--chat-text-secondary);
            font-size: 18px;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.2s ease;
            margin-right: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            flex-shrink: 0;
        }

        .emoji-btn:hover {
            background: var(--chat-hover-bg);
            color: var(--chat-text-primary);
            transform: scale(1.1);
        }

        .emoji-btn.active {
            background: var(--chat-active-bg);
            color: white;
        }

        .emoji-picker {
            position: absolute;
            bottom: 100%;
            left: 0;
            right: 0;
            background: var(--chat-bg-primary);
            border: 1px solid var(--chat-border);
            border-radius: 12px;
            box-shadow: 0 8px 32px var(--chat-shadow);
            z-index: 1000;
            margin-bottom: 8px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: all 0.3s ease;
            max-height: 300px;
            overflow: hidden;
            display: block;
        }

        .emoji-picker.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .emoji-picker-header {
            padding: 12px 16px 8px;
            border-bottom: 1px solid var(--chat-border);
            background: var(--chat-bg-secondary);
            border-radius: 12px 12px 0 0;
        }

        .emoji-categories {
            display: flex;
            gap: 4px;
            overflow-x: auto;
            padding-bottom: 4px;
        }

        .emoji-category-btn {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.2s ease;
            flex-shrink: 0;
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .emoji-category-btn:hover {
            background: var(--chat-hover-bg);
            transform: scale(1.1);
        }

        .emoji-category-btn.active {
            background: var(--chat-active-bg);
            color: white;
        }

        .emoji-picker-content {
            padding: 12px;
            max-height: 200px;
            overflow-y: auto;
        }

        .emoji-grid {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 4px;
        }

        .emoji-item {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 40px;
        }

        .emoji-item:hover {
            background: var(--chat-hover-bg);
            transform: scale(1.2);
        }

        /* Dark theme adjustments for emoji picker */
        [data-theme="dark"] .emoji-picker {
            background: var(--chat-bg-primary);
            border-color: var(--chat-border);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }

        [data-theme="dark"] .emoji-picker-header {
            background: var(--chat-bg-secondary);
            border-bottom-color: var(--chat-border);
        }

        /* Responsive emoji picker */
        @media (max-width: 768px) {
            .emoji-picker {
                left: 12px;
                right: 12px;
                max-height: 250px;
            }

            .emoji-grid {
                grid-template-columns: repeat(6, 1fr);
            }

            .emoji-item {
                font-size: 20px;
                min-height: 36px;
            }

            .emoji-category-btn {
                min-width: 36px;
                height: 36px;
                font-size: 18px;
            }
        }

        /* Welcome Screen */
        .welcome-screen {
            flex: 1;
            height: calc(100vh - 60px);
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--chat-bg-secondary);
        }

        .welcome-content {
            text-align: center;
            max-width: 400px;
            padding: 40px;
        }

        .welcome-icon {
            font-size: 80px;
            color: #667eea;
            margin-bottom: 24px;
        }

        .welcome-content h2 {
            font-size: 28px;
            font-weight: 700;
            color: var(--chat-text-primary);
            margin-bottom: 12px;
        }

        .welcome-content p {
            font-size: 16px;
            color: var(--chat-text-secondary);
            margin-bottom: 32px;
            line-height: 1.5;
        }

        .welcome-features {
            display: flex;
            justify-content: space-around;
            gap: 20px;
        }

        .feature {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            color: var(--chat-text-secondary);
        }

        .feature i {
            font-size: 24px;
            color: #667eea;
        }

        .feature span {
            font-size: 12px;
            font-weight: 500;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .chat-container {
                flex-direction: column;
                height: calc(100vh - 60px);
                width: 100%;
                margin-left: 0;
                margin-top: 82px;
            }

            .contacts-sidebar {
                width: 100%;
                height: calc(50vh - 30px);
                border-right: none;
                border-bottom: 1px solid #e5e7eb;
                flex-shrink: 0;
            }

            .chat-area {
                height: calc(50vh - 30px);
                flex: 1;
            }

            .messages {
                height: calc(50vh - 150px);
                overflow-y: auto;
                overflow-x: hidden;
            }

            .welcome-screen {
                height: calc(50vh - 30px);
            }
        }

        /* Enhanced Contacts Sidebar */
        .contacts-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding: 0 1rem;
        }

        .contacts-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
        }

        .online-count {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #666;
        }

        .online-dot {
            width: 8px;
            height: 8px;
            background: #4CAF50;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }

        .search-container {
            position: relative;
            margin: 1rem;
            margin-bottom: 1.5rem;
        }

        .search-container i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 0.9rem;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid var(--chat-border);
            border-radius: 25px;
            background: var(--chat-bg-secondary);
            color: var(--chat-text-primary);
            font-size: 0.9rem;
            outline: none;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            border-color: #667eea;
            background: var(--chat-bg-primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .section-title {
            font-size: 0.8rem;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 1rem 1rem 0.5rem 1rem;
        }

        .users-section,
        .recent-section {
            margin-bottom: 1rem;
        }

        .contact-info {
            flex: 1;
            min-width: 0;
        }

        .status-indicator {
            font-size: 0.75rem;
            color: #4CAF50;
            font-weight: 500;
        }

        .status-indicator.offline {
            color: #999;
        }

        .message-time {
            font-size: 0.75rem;
            color: #999;
            margin-top: 0.25rem;
        }

        .badge.unread {
            background: #ff4757;
            color: white;
            font-weight: 600;
        }

        /* Enhanced Chat Interface */
        .contact-info-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
        }

        .back-btn {
            background: none;
            border: none;
            color: #666;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .back-btn:hover {
            background: #f0f0f0;
            color: #333;
        }

        .contact-details {
            flex: 1;
            min-width: 0;
        }

        .online-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #4CAF50;
            font-weight: 500;
        }

        .chat-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            background: none;
            border: none;
            color: #666;
            font-size: 1.1rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .action-btn:hover {
            background: #f0f0f0;
            color: #333;
        }

        /* Enhanced Messages */
        .time-divider {
            text-align: center;
            margin: 1.5rem 0;
            position: relative;
        }

        .time-divider span {
            background: #f0f0f0;
            color: #666;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .message-wrapper {
            margin: 0.5rem 0;
            position: relative;
        }

        .message-wrapper:hover .message-actions {
            opacity: 1;
        }

        .message-actions {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0;
            transition: opacity 0.2s ease;
            display: flex;
            gap: 0.25rem;
            background: rgba(255, 255, 255, 0.9);
            padding: 0.25rem;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .own-message .message-actions {
            right: -60px;
        }

        .other-message .message-actions {
            left: -60px;
        }

        .message-action-btn {
            background: none;
            border: none;
            color: #666;
            font-size: 0.9rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .message-action-btn:hover {
            background: #f0f0f0;
            color: #333;
        }

        .message-content {
            margin-bottom: 0.25rem;
        }

        .message-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.75rem;
            opacity: 0.7;
        }

        .message-status {
            color: #4CAF50;
        }

        .empty-chat {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            color: #666;
        }

        .empty-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .empty-chat h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.2rem;
            color: #333;
        }

        .empty-chat p {
            margin: 0;
            font-size: 0.9rem;
        }

        /* Typing Indicator */
        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem;
            color: #666;
            font-style: italic;
        }

        .typing-dots {
            display: flex;
            gap: 0.25rem;
        }

        .typing-dot {
            width: 6px;
            height: 6px;
            background: #999;
            border-radius: 50%;
            animation: typing 1.4s infinite ease-in-out;
        }

        .typing-dot:nth-child(1) {
            animation-delay: -0.32s;
        }

        .typing-dot:nth-child(2) {
            animation-delay: -0.16s;
        }

        @keyframes typing {

            0%,
            80%,
            100% {
                transform: scale(0.8);
                opacity: 0.5;
            }

            40% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Enhanced Input Area */
        .input-actions {
            display: flex;
            gap: 0.5rem;
            margin-right: 1rem;
        }

        .input-action-btn {
            background: none;
            border: none;
            color: #666;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: all 0.2s ease;
        }

        .input-action-btn:hover {
            background: #f0f0f0;
            color: #333;
        }

        .input-field {
            display: flex;
            align-items: center;
            background: var(--chat-bg-secondary);
            border-radius: 25px;
            padding: 0.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            flex: 1;
            border: 1px solid var(--chat-border);
        }

        .message-input {
            flex: 1;
            border: none;
            outline: none;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            background: transparent;
            color: var(--chat-text-primary);
        }

        .send-btn {
            background: #667eea;
            border: none;
            color: white;
            font-size: 1rem;
            cursor: pointer;
            padding: 0.75rem;
            border-radius: 50%;
            transition: all 0.2s ease;
            margin-left: 0.5rem;
        }

        .send-btn:hover:not(:disabled) {
            background: #5a6fd8;
            transform: scale(1.05);
        }

        .send-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .pic {
            width: 4rem;
            height: 4rem;
            background-size: cover;
            background-position: center;
            border-radius: 50%;
        }

        .contact {
            position: relative;
            margin-bottom: 1rem;
            padding-left: 5rem;
            height: 4.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }

        .contact:hover {
            background-color: rgba(0, 0, 0, 0.05);
            border-radius: 0.5rem;
        }

        .contact .pic {
            position: absolute;
            left: 0;
        }

        .contact .name {
            font-weight: 500;
            margin-bottom: 0.125rem;
            color: #333;
        }

        .contact .message,
        .contact .seen {
            font-size: 0.9rem;
            color: #999;
        }

        .contact .badge {
            box-sizing: border-box;
            position: absolute;
            width: 1.5rem;
            height: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
            padding-top: 0.125rem;
            border-radius: 1rem;
            top: 0;
            left: 2.5rem;
            background: #333;
            color: white;
        }

        .contact .badge.online {
            background: #4CAF50;
            width: 1rem;
            height: 1rem;
            left: 3rem;
            top: 0.25rem;
        }

        .contacts {
            position: absolute;
            top: 50%;
            left: 0;
            transform: translate(-1rem, -50%);
            width: 24rem;
            height: 32rem;
            padding: 1rem 2rem 1rem 1rem;
            box-sizing: border-box;
            border-radius: 1rem 0 0 1rem;
            cursor: pointer;
            background: white;
            box-shadow:
                0 0 8rem 0 rgba(0, 0, 0, 0.1),
                2rem 2rem 4rem -3rem rgba(0, 0, 0, 0.5);
            transition: transform 500ms;
            overflow-y: auto;
            z-index: 10;
        }

        .contacts:hover {
            transform: translate(0, -50%);
        }

        .contacts h2 {
            margin: 0.5rem 0 1.5rem 5rem;
            color: #333;
        }

        .contacts .fa-bars {
            position: absolute;
            left: 2.25rem;
            color: #999;
            transition: color 200ms;
            cursor: pointer;
        }

        .contacts .fa-bars:hover {
            color: #666;
        }

        .contacts .contact:last-child {
            margin: 0;
        }

        .chat {
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            width: 24rem;
            height: 38rem;
            z-index: 2;
            box-sizing: border-box;
            border-radius: 1rem;
            background: white;
            box-shadow:
                0 0 8rem 0 rgba(0, 0, 0, 0.1),
                0rem 2rem 4rem -3rem rgba(0, 0, 0, 0.5);
        }

        .chat .contact.bar {
            flex-basis: 3.5rem;
            flex-shrink: 0;
            margin: 1rem;
            box-sizing: border-box;
            border-bottom: 1px solid #eee;
            padding-bottom: 1rem;
        }

        .chat .messages {
            padding: 1rem;
            background: #F7F7F7;
            flex-shrink: 2;
            overflow-y: auto;
            box-shadow:
                inset 0 2rem 2rem -2rem rgba(0, 0, 0, 0.05),
                inset 0 -2rem 2rem -2rem rgba(0, 0, 0, 0.05);
        }

        .chat .messages .time {
            font-size: 0.8rem;
            background: #EEE;
            padding: 0.25rem 1rem;
            border-radius: 2rem;
            color: #999;
            width: fit-content;
            margin: 0 auto 1rem auto;
        }

        .chat .messages .message {
            box-sizing: border-box;
            padding: 0.5rem 1rem;
            margin: 1rem;
            background: #FFF;
            border-radius: 1.125rem 1.125rem 1.125rem 0;
            min-height: 2.25rem;
            width: fit-content;
            max-width: 66%;
            box-shadow:
                0 0 2rem rgba(0, 0, 0, 0.075),
                0rem 1rem 1rem -1rem rgba(0, 0, 0, 0.1);
        }

        .chat .messages .message.parker {
            margin: 1rem 1rem 1rem auto;
            border-radius: 1.125rem 1.125rem 0 1.125rem;
            background: #333;
            color: white;
        }

        .chat .input {
            box-sizing: border-box;
            flex-basis: 4rem;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            padding: 0 0.5rem 0 1.5rem;
        }

        .chat .input i {
            font-size: 1.5rem;
            margin-right: 1rem;
            color: #666;
            cursor: pointer;
            transition: color 200ms;
        }

        .chat .input i:hover {
            color: #333;
        }

        .chat .input input {
            border: none;
            background-image: none;
            background-color: white;
            padding: 0.5rem 1rem;
            margin-right: 1rem;
            border-radius: 1.125rem;
            flex-grow: 2;
            box-shadow:
                0 0 1rem rgba(0, 0, 0, 0.1),
                0rem 1rem 1rem -1rem rgba(0, 0, 0, 0.2);
            font-family: 'Red Hat Display', sans-serif;
            font-weight: 400;
            letter-spacing: 0.025em;
            outline: none;
        }

        .chat .input input::placeholder {
            color: #999;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .chat-container {
                height: 100vh;
                padding: 0;
            }

            .contacts {
                width: 100%;
                height: 100%;
                transform: none;
                position: fixed;
                top: 0;
                left: 0;
                border-radius: 0;
                z-index: 1000;
            }

            .chat {
                width: 100%;
                height: 100%;
                border-radius: 0;
            }
        }

        /* Desktop hover behavior */
        @media (min-width: 769px) {
            .contacts {
                transform: translate(-1rem, -50%);
            }

            .contacts:hover {
                transform: translate(0, -50%);
            }
        }

        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .chat-container {
                background: #1a1a1a;
                color: #fff;
            }

            .contacts,
            .chat {
                background: #2d2d2d;
                color: #fff;
            }

            .contact .name {
                color: #fff;
            }

            .contact .message,
            .contact .seen {
                color: #ccc;
            }

            .chat .messages {
                background: #1a1a1a;
            }

            .chat .messages .message {
                background: #2d2d2d;
                color: #fff;
            }

            .chat .messages .message.parker {
                background: #4a5568;
            }

            .chat .input input {
                background-color: #2d2d2d;
                color: #fff;
            }
        }

        /* Enhanced Mobile Responsive Design */
        @media (max-width: 768px) {
            .chat-container {
                flex-direction: column;
                height: 100vh;
                width: 100%;
                margin-left: 0;
                margin-top: 0;
                padding: 0;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
            }

            .contacts-sidebar {
                width: 100%;
                height: 100%;
                border-right: none;
                border-bottom: none;
                position: absolute;
                top: 0;
                left: 0;
                z-index: 1000;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .contacts-sidebar.show {
                transform: translateX(0);
            }

            .chat-area {
                height: 100vh;
                flex: 1;
                position: relative;
                z-index: 1;
            }

            .chat-header {
                padding: 12px 16px;
                border-bottom: 1px solid var(--chat-border);
                background: var(--chat-bg-primary);
                position: sticky;
                top: 0;
                z-index: 100;
            }

            .chat-user-info {
                display: flex;
                align-items: center;
                gap: 12px;
            }

            .chat-user-info .user-avatar {
                width: 40px;
                height: 40px;
            }

            .chat-user-info .user-name {
                font-size: 16px;
                font-weight: 600;
            }

            .chat-actions {
                gap: 8px;
            }

            .action-btn {
                width: 36px;
                height: 36px;
                padding: 8px;
            }

            .messages {
                padding: 12px 16px;
                height: calc(100vh - 140px);
                overflow-y: auto;
            }

            .message {
                margin-bottom: 12px;
                max-width: 85%;
            }

            .message-content {
                padding: 10px 14px;
                font-size: 14px;
                line-height: 1.4;
            }

            .message-time {
                font-size: 11px;
                margin-top: 4px;
            }

            .input-field {
                padding: 8px 12px;
                margin: 12px 16px;
                border-radius: 20px;
            }

            .message-input {
                padding: 8px 12px;
                font-size: 14px;
            }

            .send-btn {
                width: 36px;
                height: 36px;
                padding: 8px;
            }

            .emoji-btn {
                width: 32px;
                height: 32px;
                padding: 6px;
            }

            /* Mobile Header Buttons */
            .header-action-buttons {
                flex-direction: row;
                gap: 8px;
                margin-top: 8px;
            }

            .header-btn {
                padding: 8px 12px;
                font-size: 12px;
                min-width: auto;
            }

            .header-btn span {
                display: none;
            }

            .header-btn i {
                font-size: 14px;
            }

            /* Mobile Search */
            .search-container {
                padding: 8px 16px;
            }

            .search-input {
                padding: 8px 12px 8px 32px;
                font-size: 14px;
            }

            .search-icon {
                left: 10px;
                font-size: 12px;
            }

            /* Mobile Contact Items */
            .contact-item {
                padding: 12px 16px;
            }

            .contact-avatar {
                width: 40px;
                height: 40px;
            }

            .contact-name {
                font-size: 14px;
            }

            .contact-email {
                font-size: 12px;
            }

            .last-message-preview {
                font-size: 12px;
            }

            /* Mobile Emoji Picker */
            .emoji-picker {
                left: 16px;
                right: 16px;
                max-height: 200px;
                bottom: 80px;
            }

            .emoji-picker-content {
                padding: 8px;
                max-height: 150px;
            }

            .emoji-item {
                width: 32px;
                height: 32px;
                font-size: 16px;
            }

            /* Mobile Welcome Screen */
            .welcome-content {
                padding: 20px;
            }

            .welcome-content h2 {
                font-size: 24px;
            }

            .welcome-features {
                flex-direction: column;
                gap: 16px;
            }

            .feature {
                flex-direction: row;
                align-items: center;
                gap: 12px;
            }

            .feature i {
                font-size: 20px;
            }

            /* Mobile Back Button */
            .back-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 40px;
                height: 40px;
                background: var(--chat-bg-secondary);
                border: 1px solid var(--chat-border);
                border-radius: 50%;
                color: var(--chat-text-primary);
                font-size: 16px;
                margin-right: 12px;
                transition: all 0.2s ease;
            }

            .back-btn:hover {
                background: var(--chat-hover-bg);
                transform: scale(1.05);
            }
        }

        /* Tablet Responsive Design */
        @media (min-width: 769px) and (max-width: 1024px) {
            .contacts-sidebar {
                width: 320px;
            }

            .chat-area {
                flex: 1;
            }

            .message {
                max-width: 70%;
            }

            .header-btn span {
                display: none;
            }

            .header-btn {
                padding: 8px 12px;
                min-width: auto;
            }
        }

        /* Small Mobile Devices */
        @media (max-width: 480px) {
            .chat-header {
                padding: 10px 12px;
            }

            .chat-user-info .user-name {
                font-size: 14px;
            }

            .messages {
                padding: 8px 12px;
                height: calc(100vh - 120px);
            }

            .message {
                max-width: 90%;
            }

            .message-content {
                padding: 8px 12px;
                font-size: 13px;
            }

            .input-field {
                margin: 8px 12px;
                padding: 6px 10px;
            }

            .message-input {
                padding: 6px 10px;
                font-size: 13px;
            }

            .contact-item {
                padding: 10px 12px;
            }

            .contact-avatar {
                width: 36px;
                height: 36px;
            }

            .header-btn {
                padding: 6px 8px;
                font-size: 11px;
            }

            .search-input {
                padding: 6px 10px 6px 28px;
                font-size: 13px;
            }
        }
    </style>

    <script>
        // Basic emoji data with only common emojis
        const emojiData = {
            smileys: ['😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰',
                '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🥸', '🤩', '🥳',
                '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣', '😖', '😫', '😩', '🥺', '😢', '😭', '😤',
                '😠', '😡', '🤬', '🤯', '😳', '🥵', '🥶', '😱', '😨', '😰', '😥', '😓', '🤗', '🤔', '🤭', '🤫',
                '🤥', '😶', '😐', '😑', '😬', '🙄', '😯', '😦', '😧', '😮', '😲', '🥱', '😴', '🤤', '😪', '😵',
                '🤐', '🥴', '🤢', '🤮', '🤧', '😷', '🤒', '🤕', '🤑', '🤠'
            ],
            hearts: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖',
                '💘', '💝'
            ],
            hands: ['👍', '👎', '👌', '🤏', '✌️', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆', '👇', '☝️', '✋', '🤚',
                '🖐️', '🖖', '👋', '🤙', '💪', '🦾', '🦿', '🦵', '🦶', '👂', '🦻', '👃', '🧠', '🦷', '🦴', '👀',
                '👁️', '👅', '👄'
            ],
            symbols: ['💯', '💢', '💬', '👁️‍🗨️', '🗨️', '🗯️', '💭', '💤', '🌍', '🌎', '🌏', '🌐', '🧭', '🏔️', '⛰️',
                '🌋', '🗻', '🏕️', '🏖️', '🏜️', '🏝️', '🏞️', '🏟️', '🏛️', '🏗️', '🧱', '🏘️', '🏚️', '🏠', '🏡',
                '🏢', '🏣', '🏤', '🏥', '🏦', '🏨', '🏩', '🏪', '🏫', '🏬', '🏭', '🏯', '🏰', '💒', '🗼', '🗽', '⛪',
                '🕌', '🛕', '🕍', '⛩️', '🕋', '⛲', '⛺', '🌁', '🌃', '🏙️', '🌄', '🌅', '🌆', '🌇', '🌉', '♨️', '🎠',
                '🎡', '🎢', '💈', '🎪', '🚂', '🚃', '🚄', '🚅', '🚆', '🚇', '🚈', '🚉', '🚊', '🚝', '🚞', '🚋',
                '🚌', '🚍', '🚎', '🚐', '🚑', '🚒', '🚓', '🚔', '🚕', '🚖', '🚗', '🚘', '🚙', '🚚', '🚛', '🚜',
                '🏎️', '🏍️', '🛵', '🚲', '🛴', '🛹', '🚏', '🛣️', '🛤️', '🛢️', '⛽', '🚨', '🚥', '🚦', '🛑', '🚧',
                '⚓', '⛵', '🛶', '🚤', '🛳️', '⛴️', '🛥️', '🚢', '✈️', '🛩️', '🛫', '🛬', '💺', '🚁', '🚟', '🚠',
                '🚡', '🛰️', '🚀', '🛸', '🎆', '🎇', '🎑', '💴', '💵', '💶', '💷', '🗿', '🛂', '🛃', '🛄', '🛅'
            ]
        };

        // Auto-scroll functionality
        function scrollToBottom() {
            const chat = document.getElementById('chat');
            if (chat) {
                // Use setTimeout to ensure DOM is updated
                setTimeout(() => {
                    chat.scrollTop = chat.scrollHeight;
                }, 100);
            }
        }

        // Polling control system
        let pollingEnabled = true;
        let pollingInterval = null;

        // Function to disable polling
        function disablePolling() {
            if (pollingInterval) {
                clearInterval(pollingInterval);
                pollingInterval = null;
            }
            pollingEnabled = false;
            console.log('Polling disabled');
        }

        // Function to enable polling
        function enablePolling() {
            if (!pollingEnabled) {
                pollingEnabled = true;
                console.log('Polling enabled');
            }
        }

        // Monitor active tab and control polling
        function monitorTabState() {
            setInterval(() => {
                const savedView = localStorage.getItem('contactsView');
                const allHeaderBtn = document.getElementById('allHeaderBtn');
                
                if (savedView === 'all' && allHeaderBtn && allHeaderBtn.classList.contains('active')) {
                    // All contacts is active - disable polling
                    if (pollingEnabled) {
                        disablePolling();
                    }
                } else {
                    // Recent or Search is active - enable polling
                    if (!pollingEnabled) {
                        enablePolling();
                    }
                }
            }, 500); // Check every 500ms
        }

        // Robust state restoration function
        function restoreActiveTabState() {
            const recentHeaderBtn = document.getElementById('recentHeaderBtn');
            const allHeaderBtn = document.getElementById('allHeaderBtn');
            const searchHeaderBtn = document.getElementById('searchHeaderBtn');
            const searchContainer = document.getElementById('searchContainer');
            const recentSection = document.getElementById('recentSection');
            const allContactsSection = document.getElementById('allContactsSection');

            if (recentHeaderBtn && allHeaderBtn && searchHeaderBtn && searchContainer && recentSection && allContactsSection) {
                const savedView = localStorage.getItem('contactsView') || 'recent';

                if (savedView === 'recent') {
                    recentHeaderBtn.classList.add('active');
                    allHeaderBtn.classList.remove('active');
                    searchHeaderBtn.classList.remove('active');
                    recentSection.style.display = 'block';
                    allContactsSection.style.display = 'none';
                    searchContainer.style.display = 'none';
                } else if (savedView === 'all') {
                    allHeaderBtn.classList.add('active');
                    recentHeaderBtn.classList.remove('active');
                    searchHeaderBtn.classList.remove('active');
                    recentSection.style.display = 'none';
                    allContactsSection.style.display = 'block';
                    searchContainer.style.display = 'none';
                } else if (savedView === 'search') {
                    searchHeaderBtn.classList.add('active');
                    recentHeaderBtn.classList.remove('active');
                    allHeaderBtn.classList.remove('active');
                    recentSection.style.display = 'none';
                    allContactsSection.style.display = 'block';
                    searchContainer.style.display = 'block';
                }
            }
        }

        // Continuous state monitoring (backup mechanism)
        function startStateMonitoring() {
            setInterval(() => {
                const savedView = localStorage.getItem('contactsView');
                const allHeaderBtn = document.getElementById('allHeaderBtn');
                const recentHeaderBtn = document.getElementById('recentHeaderBtn');
                
                if (savedView === 'all' && allHeaderBtn && !allHeaderBtn.classList.contains('active')) {
                    console.log('State mismatch detected, restoring All contacts view');
                    restoreActiveTabState();
                } else if (savedView === 'recent' && recentHeaderBtn && !recentHeaderBtn.classList.contains('active')) {
                    console.log('State mismatch detected, restoring Recent contacts view');
                    restoreActiveTabState();
                }
            }, 1000); // Check every second
        }

        // Enhanced polling control - intercept Livewire polling
        function interceptLivewirePolling() {
            // Override Livewire's polling mechanism
            const originalPoll = Livewire.poll;
            if (originalPoll) {
                Livewire.poll = function(interval, method) {
                    const savedView = localStorage.getItem('contactsView');
                    if (savedView === 'all') {
                        console.log('Blocking Livewire polling - All contacts is active');
                        return; // Don't poll when All contacts is active
                    }
                    return originalPoll.call(this, interval, method);
                };
            }
        }

        // Alternative approach - disable polling via DOM manipulation
        function controlPollingViaDOM() {
            setInterval(() => {
                const savedView = localStorage.getItem('contactsView');
                const chatContainer = document.querySelector('.chat-container');
                
                if (savedView === 'all') {
                    // Remove polling attribute when All contacts is active
                    if (chatContainer && chatContainer.hasAttribute('wire:poll.5s')) {
                        chatContainer.removeAttribute('wire:poll.5s');
                        console.log('Removed polling attribute - All contacts active');
                    }
                } else {
                    // Add polling attribute when Recent or Search is active
                    if (chatContainer && !chatContainer.hasAttribute('wire:poll.5s')) {
                        chatContainer.setAttribute('wire:poll.5s', 'refreshComponent');
                        console.log('Added polling attribute - Recent/Search active');
                    }
                }
            }, 1000); // Check every second
        }

        // Initialize when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-scroll on page load if there are messages
            scrollToBottom();

            // Start state monitoring
            startStateMonitoring();

            // Start polling control
            controlPollingViaDOM();

            // Header button functionality
            const recentHeaderBtn = document.getElementById('recentHeaderBtn');
            const allHeaderBtn = document.getElementById('allHeaderBtn');
            const searchHeaderBtn = document.getElementById('searchHeaderBtn');
            const searchContainer = document.getElementById('searchContainer');
            const recentSection = document.getElementById('recentSection');
            const allContactsSection = document.getElementById('allContactsSection');

            // Header button functionality
            if (recentHeaderBtn && allHeaderBtn && searchHeaderBtn && searchContainer) {
                // Restore previous view state from localStorage
                const savedView = localStorage.getItem('contactsView') || 'recent';

                // Set initial state based on saved view
                if (savedView === 'recent') {
                    recentHeaderBtn.classList.add('active');
                    allHeaderBtn.classList.remove('active');
                    searchHeaderBtn.classList.remove('active');
                    recentSection.style.display = 'block';
                    allContactsSection.style.display = 'none';
                    searchContainer.style.display = 'none';
                } else if (savedView === 'all') {
                    allHeaderBtn.classList.add('active');
                    recentHeaderBtn.classList.remove('active');
                    searchHeaderBtn.classList.remove('active');
                    recentSection.style.display = 'none';
                    allContactsSection.style.display = 'block';
                    searchContainer.style.display = 'none';
                } else if (savedView === 'search') {
                    searchHeaderBtn.classList.add('active');
                    recentHeaderBtn.classList.remove('active');
                    allHeaderBtn.classList.remove('active');
                    recentSection.style.display = 'none';
                    allContactsSection.style.display = 'block'; // Show all contacts for search
                    searchContainer.style.display = 'block';
                }

                recentHeaderBtn.addEventListener('click', function() {
                    // Switch to recent view
                    recentHeaderBtn.classList.add('active');
                    allHeaderBtn.classList.remove('active');
                    searchHeaderBtn.classList.remove('active');
                    recentSection.style.display = 'block';
                    allContactsSection.style.display = 'none';
                    searchContainer.style.display = 'none';

                    // Clear any active search
                    if (searchInput) {
                        searchInput.value = '';
                        if (searchClear) {
                            searchClear.style.display = 'none';
                        }
                    }

                    // Save state
                    localStorage.setItem('contactsView', 'recent');

                    // Immediately enable polling for Recent contacts
                    const chatContainer = document.querySelector('.chat-container');
                    if (chatContainer && !chatContainer.hasAttribute('wire:poll.5s')) {
                        chatContainer.setAttribute('wire:poll.5s', 'refreshComponent');
                        console.log('Immediately enabled polling - switched to Recent contacts');
                    }
                });

                allHeaderBtn.addEventListener('click', function() {
                    // Switch to all contacts view
                    allHeaderBtn.classList.add('active');
                    recentHeaderBtn.classList.remove('active');
                    searchHeaderBtn.classList.remove('active');
                    recentSection.style.display = 'none';
                    allContactsSection.style.display = 'block';
                    searchContainer.style.display = 'none';

                    // Clear any active search
                    if (searchInput) {
                        searchInput.value = '';
                        if (searchClear) {
                            searchClear.style.display = 'none';
                        }
                    }

                    // Save state
                    localStorage.setItem('contactsView', 'all');

                    // Immediately disable polling for All contacts
                    const chatContainer = document.querySelector('.chat-container');
                    if (chatContainer && chatContainer.hasAttribute('wire:poll.5s')) {
                        chatContainer.removeAttribute('wire:poll.5s');
                        console.log('Immediately disabled polling - switched to All contacts');
                    }
                });

                searchHeaderBtn.addEventListener('click', function() {
                    // Switch to search view
                    searchHeaderBtn.classList.add('active');
                    recentHeaderBtn.classList.remove('active');
                    allHeaderBtn.classList.remove('active');
                    recentSection.style.display = 'none';
                    allContactsSection.style.display = 'block'; // Show all contacts for search
                    searchContainer.style.display = 'block';

                    // Save state
                    localStorage.setItem('contactsView', 'search');

                    // Immediately enable polling for Search
                    const chatContainer = document.querySelector('.chat-container');
                    if (chatContainer && !chatContainer.hasAttribute('wire:poll.5s')) {
                        chatContainer.setAttribute('wire:poll.5s', 'refreshComponent');
                        console.log('Immediately enabled polling - switched to Search');
                    }

                    // Focus on search input
                    const searchInput = document.getElementById('contactSearch');
                    if (searchInput) {
                        setTimeout(() => searchInput.focus(), 100);
                    }
                });
            }

            // Search functionality
            const searchInput = document.getElementById('contactSearch');
            const searchClear = document.getElementById('searchClear');
            const usersList = document.getElementById('usersList');
            const recentList = document.querySelector('.recent-list');

            if (searchInput && searchClear && usersList) {
                // Search input event listener
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();

                    if (searchTerm.length > 0) {
                        // Show clear button
                        searchClear.style.display = 'block';

                        // Filter contacts
                        filterContacts(searchTerm);
                    } else {
                        // Hide clear button
                        searchClear.style.display = 'none';

                        // Show all contacts
                        showAllContacts();
                    }
                });

                // Clear search button
                searchClear.addEventListener('click', function() {
                    searchInput.value = '';
                    searchInput.focus();
                    searchClear.style.display = 'none';
                    showAllContacts();
                });

                // Function to filter contacts
                function filterContacts(searchTerm) {
                    const contactItems = usersList.querySelectorAll('.contact-item');
                    let hasResults = false;

                    contactItems.forEach(item => {
                        const name = item.getAttribute('data-name') || '';
                        const email = item.getAttribute('data-email') || '';

                        if (name.includes(searchTerm) || email.includes(searchTerm)) {
                            item.style.display = 'flex';
                            hasResults = true;
                        } else {
                            item.style.display = 'none';
                        }
                    });

                    // Show "No results" message if no contacts match
                    showNoResultsMessage(hasResults);
                }

                // Function to show all contacts
                function showAllContacts() {
                    const contactItems = usersList.querySelectorAll('.contact-item');
                    contactItems.forEach(item => {
                        item.style.display = 'flex';
                    });
                    hideNoResultsMessage();
                }

                // Function to show/hide no results message
                function showNoResultsMessage(hasResults) {
                    let noResultsMsg = document.getElementById('no-results-message');

                    if (!hasResults) {
                        if (!noResultsMsg) {
                            noResultsMsg = document.createElement('div');
                            noResultsMsg.id = 'no-results-message';
                            noResultsMsg.className = 'no-results-message';
                            noResultsMsg.innerHTML = `
                                <div class="no-results-content">
                                    <i class="fas fa-search"></i>
                                    <h3>No contacts found</h3>
                                    <p>Try searching with a different term</p>
                                </div>
                            `;
                            usersList.appendChild(noResultsMsg);
                        }
                        noResultsMsg.style.display = 'block';
                    } else {
                        hideNoResultsMessage();
                    }
                }

                function hideNoResultsMessage() {
                    const noResultsMsg = document.getElementById('no-results-message');
                    if (noResultsMsg) {
                        noResultsMsg.style.display = 'none';
                    }
                }
            }

            // Set up emoji picker
            initEmojiPicker();

            // Close emoji picker when clicking outside
            document.addEventListener('click', function(e) {
                const emojiPicker = document.getElementById('emoji-picker');
                const emojiButton = document.getElementById('emoji-button');

                if (emojiPicker && emojiButton &&
                    !emojiPicker.contains(e.target) &&
                    !emojiButton.contains(e.target)) {
                    closeEmojiPicker();
                }
            });
        });

        // Enhanced emoji picker functions
        function toggleEmojiPicker() {
            const picker = document.getElementById('emoji-picker');
            const btn = document.getElementById('emoji-button');

            if (picker && btn) {
                if (picker.classList.contains('show')) {
                    closeEmojiPicker();
                } else {
                    openEmojiPicker();
                }
            }
        }

        function openEmojiPicker() {
            const picker = document.getElementById('emoji-picker');
            const btn = document.getElementById('emoji-button');

            if (picker && btn) {
                picker.classList.add('show');
                btn.classList.add('active');
                loadEmojis('smileys'); // Load default category
            }
        }

        function closeEmojiPicker() {
            const picker = document.getElementById('emoji-picker');
            const btn = document.getElementById('emoji-button');

            if (picker && btn) {
                picker.classList.remove('show');
                btn.classList.remove('active');
            }
        }

        function loadEmojis(category = 'smileys') {
            const emojiGrid = document.getElementById('emoji-grid');
            if (!emojiGrid) return;

            const emojis = emojiData[category] || [];
            emojiGrid.innerHTML = '';

            emojis.forEach(emoji => {
                const emojiButton = document.createElement('button');
                emojiButton.className = 'emoji-item';
                emojiButton.textContent = emoji;
                emojiButton.title = emoji;

                emojiButton.addEventListener('click', function() {
                    insertEmoji(emoji);
                });

                emojiGrid.appendChild(emojiButton);
            });
        }

        function switchCategory(category) {
            loadEmojis(category);

            // Update active category button
            const categoryButtons = document.querySelectorAll('.emoji-category-btn');
            categoryButtons.forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.category === category) {
                    btn.classList.add('active');
                }
            });
        }

        function insertEmoji(emoji) {
            const messageInput = document.getElementById('message-input');
            if (!messageInput) return;

            const cursorPosition = messageInput.selectionStart || 0;
            const currentValue = messageInput.value;

            // Insert emoji at cursor position
            const newValue = currentValue.substring(0, cursorPosition) +
                emoji +
                currentValue.substring(cursorPosition);

            messageInput.value = newValue;

            // Update cursor position
            const newCursorPosition = cursorPosition + emoji.length;
            messageInput.setSelectionRange(newCursorPosition, newCursorPosition);

            // Trigger Livewire update
            messageInput.dispatchEvent(new Event('input', {
                bubbles: true
            }));
            messageInput.focus();

            // Close emoji picker
            closeEmojiPicker();
        }

        function initEmojiPicker() {
            const emojiButton = document.getElementById('emoji-button');
            const categoryButtons = document.querySelectorAll('.emoji-category-btn');

            // Set up category buttons
            categoryButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    const category = this.getAttribute('data-category');
                    switchCategory(category);
                });
            });

            // Load initial emojis
            loadEmojis('smileys');
        }

        // Enhanced auto-scroll for Livewire events
        document.addEventListener('livewire:init', () => {
            // Scroll when component updates
            Livewire.hook('commit', ({
                component,
                commit,
                respond,
                succeed,
                fail
            }) => {
                succeed(() => {
                    // Scroll after any Livewire update
                    setTimeout(() => {
                        scrollToBottom();
                    }, 50);
                });
            });
        });

        // Listen for custom events
        document.addEventListener('message-sent', () => {
            scrollToBottom();
        });

        document.addEventListener('message-received', () => {
            scrollToBottom();
        });

        // Real-time input handler
        function handleRealTimeInput() {
            const input = document.getElementById('message-input');
            if (input) {
                // Check if the input contains emojis
                const value = input.value;
                const hasEmojis =
                    /[\u{1F600}-\u{1F64F}]|[\u{1F300}-\u{1F5FF}]|[\u{1F680}-\u{1F6FF}]|[\u{1F1E0}-\u{1F1FF}]|[\u{2600}-\u{26FF}]|[\u{2700}-\u{27BF}]/u
                    .test(value);

                // Update emoji counter
                const emojiCounter = document.getElementById('emoji-counter');
                if (emojiCounter) {
                    if (hasEmojis) {
                        const emojiMatch = value.match(
                            /[\u{1F600}-\u{1F64F}]|[\u{1F300}-\u{1F5FF}]|[\u{1F680}-\u{1F6FF}]|[\u{1F1E0}-\u{1F1FF}]|[\u{2600}-\u{26FF}]|[\u{2700}-\u{27BF}]/gu
                        );
                        const emojiCount = emojiMatch ? emojiMatch.length : 0;
                        emojiCounter.textContent = emojiCount;
                        emojiCounter.style.display = 'flex';
                    } else {
                        emojiCounter.style.display = 'none';
                    }
                }
            }
        }

        // Typing indicator - only show when other user is typing
        function handleTyping() {
            // This function should only be called by real-time events from other users
            // Not when the current user types
            const typingIndicator = document.getElementById('typingIndicator');
            if (typingIndicator) {
                typingIndicator.style.display = 'flex';

                clearTimeout(window.typingTimer);
                window.typingTimer = setTimeout(() => {
                    typingIndicator.style.display = 'none';
                }, 1000);
            }
        }

        // Function to show typing indicator for other users (called by real-time events)
        function showOtherUserTyping() {
            handleTyping();
        }

        // Mobile Navigation Functions
        function toggleContactsSidebar() {
            const sidebar = document.querySelector('.contacts-sidebar');
            if (sidebar) {
                sidebar.classList.toggle('show');
            }
        }

        function hideContactsSidebar() {
            const sidebar = document.querySelector('.contacts-sidebar');
            if (sidebar) {
                sidebar.classList.remove('show');
            }
        }

        // Mobile-specific event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Add mobile menu button to chat header
            const chatHeader = document.querySelector('.chat-header');
            if (chatHeader && window.innerWidth <= 768) {
                const mobileMenuBtn = document.createElement('button');
                mobileMenuBtn.className = 'mobile-menu-btn';
                mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
                mobileMenuBtn.onclick = toggleContactsSidebar;
                mobileMenuBtn.style.cssText = `
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    width: 40px;
                    height: 40px;
                    background: var(--chat-bg-secondary);
                    border: 1px solid var(--chat-border);
                    border-radius: 50%;
                    color: var(--chat-text-primary);
                    font-size: 16px;
                    margin-right: 12px;
                    transition: all 0.2s ease;
                    cursor: pointer;
                `;
                
                const chatUserInfo = chatHeader.querySelector('.chat-user-info');
                if (chatUserInfo) {
                    chatUserInfo.insertBefore(mobileMenuBtn, chatUserInfo.firstChild);
                }
            }

            // Hide sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    const sidebar = document.querySelector('.contacts-sidebar');
                    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
                    
                    if (sidebar && sidebar.classList.contains('show') && 
                        !sidebar.contains(e.target) && 
                        !mobileMenuBtn.contains(e.target)) {
                        hideContactsSidebar();
                    }
                }
            });

            // Hide sidebar when selecting a contact on mobile
            const contactItems = document.querySelectorAll('.contact-item');
            contactItems.forEach(item => {
                item.addEventListener('click', function() {
                    if (window.innerWidth <= 768) {
                        setTimeout(hideContactsSidebar, 100);
                    }
                });
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
                const sidebar = document.querySelector('.contacts-sidebar');
                
                if (window.innerWidth > 768) {
                    // Desktop view
                    if (mobileMenuBtn) {
                        mobileMenuBtn.style.display = 'none';
                    }
                    if (sidebar) {
                        sidebar.classList.remove('show');
                        sidebar.style.transform = 'none';
                        sidebar.style.position = 'relative';
                    }
                } else {
                    // Mobile view
                    if (mobileMenuBtn) {
                        mobileMenuBtn.style.display = 'flex';
                    }
                    if (sidebar) {
                        sidebar.style.position = 'absolute';
                        sidebar.style.transform = 'translateX(-100%)';
                    }
                }
            });
        });

        // Enhanced Livewire event listeners
        document.addEventListener('livewire:updated', function() {
            console.log('Livewire updated, restoring state...');
            setTimeout(() => {
                restoreActiveTabState();
            }, 100);
        });

        document.addEventListener('livewire:init', function() {
            console.log('Livewire init, restoring state...');
            setTimeout(() => {
                restoreActiveTabState();
            }, 100);
        });

        // Additional hook for Livewire commits
        document.addEventListener('livewire:init', () => {
            Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
                succeed(() => {
                    setTimeout(() => {
                        console.log('Livewire commit succeeded, restoring state...');
                        restoreActiveTabState();
                    }, 50);
                });
            });
        });
    </script>
</div>
