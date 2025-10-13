<?php

namespace App\Livewire;

use App\Events\DirectMessageSent;
use App\Models\DirectMessage;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Session;

class SlackLikeChatComponent extends Component
{
    public $users = [];
    public $selectedUser = null;
    public $messages = [];
    public $newMessage = '';
    public $conversations = [];
    public $showUserList = true;
    public $currentTheme = 'light';
    public $showDeleteModal = false;
    public $messageToDelete = null;
    
    protected $listeners = ['refreshComponent', 'theme-changed'];

    public function mount()
    {
        // Load current theme
        $this->currentTheme = Session::get('theme', 'light');
        
        // Load all users with online status
        $this->loadUsers();
        
        // Load conversations
        $this->loadConversations();
    }

    public function loadUsers()
    {
        $this->users = User::where('id', '!=', auth()->id())
            ->select('id', 'name', 'email', 'avatar')
            ->orderBy('name', 'asc')
            ->get()
            ->map(function ($user) {
                $userArray = $user->toArray();
                // Set default online status if column doesn't exist
                $userArray['is_online'] = isset($user->is_online) ? $user->is_online : false;
                $userArray['online_status'] = isset($user->online_status) ? $user->online_status : 'offline';
                return $userArray;
            })
            ->toArray();
    }

    public function loadConversations()
    {
        try {
            $conversations = auth()->user()->conversations();
            $this->conversations = $conversations->map(function ($conversation) {
                $userArray = $conversation['user']->toArray();
                $userArray['is_online'] = isset($conversation['user']->is_online) ? $conversation['user']->is_online : false;
                $userArray['online_status'] = isset($conversation['user']->online_status) ? $conversation['user']->online_status : 'offline';
                return [
                    'user' => $userArray,
                    'last_message' => $conversation['last_message']->toArray(),
                    'unread_count' => $conversation['unread_count']
                ];
            })->toArray();
        } catch (\Exception $e) {
            // If conversations method fails, set empty array
            $this->conversations = [];
        }
    }

    public function selectUser($userId)
    {
        $user = User::findOrFail($userId);
        $this->selectedUser = $user->toArray();
        $this->selectedUser['is_online'] = isset($user->is_online) ? $user->is_online : false;
        $this->selectedUser['online_status'] = isset($user->online_status) ? $user->online_status : 'offline';
        $this->loadMessages();
        $this->markMessagesAsRead();
        $this->showUserList = false;
    }

    public function loadMessages()
    {
        if ($this->selectedUser) {
            $messages = DirectMessage::conversation(auth()->id(), $this->selectedUser['id'])
                ->with(['sender', 'receiver'])
                ->get();
            
            $this->messages = $messages->map(function($message) {
                $messageArray = $message->toArray();
                $messageArray['sender']['avatar_url'] = $message->sender->avatar_url;
                $messageArray['receiver']['avatar_url'] = $message->receiver->avatar_url;
                return $messageArray;
            })->toArray();
        }
    }

    public function sendMessage()
    {
        // Simple validation
        if (empty($this->newMessage) || strlen($this->newMessage) > 5000) {
            return;
        }

        if (!$this->selectedUser) {
            return;
        }

        // Create the direct message
        $directMessage = DirectMessage::create([
            'sender_id' => auth()->id(),
            'receiver_id' => $this->selectedUser['id'],
            'message' => $this->newMessage,
        ]);

        // Load the relationships
        $directMessage->load(['sender', 'receiver']);

        // Broadcast the message
        broadcast(new DirectMessageSent($directMessage))->toOthers();

        // Add message to the local messages array
        $messageArray = $directMessage->toArray();
        $messageArray['sender']['avatar_url'] = $directMessage->sender->avatar_url;
        $messageArray['receiver']['avatar_url'] = $directMessage->receiver->avatar_url;
        $this->messages[] = $messageArray;

        // Reset the input
        $this->newMessage = '';

        // Update conversations without full reload to prevent blinking
        $this->updateConversationsAfterMessage();

        // Dispatch browser event to scroll to bottom
        $this->dispatch('message-sent');
    }

    public function markMessagesAsRead()
    {
        if ($this->selectedUser) {
            DirectMessage::where('sender_id', $this->selectedUser['id'])
                ->where('receiver_id', auth()->id())
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
        }
    }


    public function handleNewMessage($payload)
    {
        // Check if the message is for the current user
        if ($payload['receiver_id'] == auth()->id()) {
            // If we're currently viewing this conversation, add the message
            if ($this->selectedUser && $payload['sender_id'] == $this->selectedUser['id']) {
                $message = DirectMessage::find($payload['id']);
                if ($message) {
                    $message->load(['sender', 'receiver']);
                    $messageArray = $message->toArray();
                    $messageArray['sender']['avatar_url'] = $message->sender->avatar_url;
                    $messageArray['receiver']['avatar_url'] = $message->receiver->avatar_url;
                    $this->messages[] = $messageArray;
                    $this->dispatch('message-received');
                }
            }
            
            // Reload conversations to update unread count and last message
            $this->loadConversations();
        }
    }

    public function refreshComponent()
    {
        // Only refresh when explicitly called, not on polling
        $this->loadUsers();
        $this->loadConversations();
        if ($this->selectedUser) {
            $this->loadMessages();
        }
    }

    // Removed WebSocket event listener to fix auth.id placeholder error
    // Real-time updates will be handled through the existing handleNewMessage method

    #[On('message-sent')]
    public function handleMessageSent()
    {
        // Handle when a message is sent - refresh conversations
        $this->loadConversations();
    }

    public function updateConversationsAfterMessage()
    {
        // Update conversations efficiently without causing blinking
        // Find the conversation for the current user and update its last message
        $lastMessage = $this->messages[count($this->messages) - 1] ?? null;
        if ($lastMessage) {
            foreach ($this->conversations as $index => $conversation) {
                if ($conversation['user']['id'] == $this->selectedUser['id']) {
                    // Update the last message for this conversation
                    $this->conversations[$index]['last_message'] = [
                        'message' => $lastMessage['message'],
                        'created_at' => $lastMessage['created_at'],
                    ];
                    break;
                }
            }
        }
    }

    public function confirmDeleteMessage($messageId)
    {
        $this->messageToDelete = $messageId;
        $this->showDeleteModal = true;
    }

    public function deleteMessage()
    {
        if (!$this->messageToDelete) {
            return;
        }

        $message = DirectMessage::findOrFail($this->messageToDelete);
        
        // Check if user can delete this message (only sender can delete their own messages)
        if ($message->sender_id !== auth()->id()) {
            session()->flash('error', 'You can only delete your own messages.');
            $this->closeDeleteModal();
            return;
        }
        
        // Delete the message
        $message->delete();
        
        // Refresh messages for the current conversation
        $this->loadMessages();
        
        // Refresh conversations to update last message
        $this->loadConversations();
        
        session()->flash('success', 'Message deleted successfully.');
        $this->closeDeleteModal();
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->messageToDelete = null;
    }

    public function manualRefresh()
    {
        // Manual refresh method that can be called from the UI
        $this->refreshComponent();
    }

    public function updatedCurrentTheme()
    {
        // This method will be called when the theme changes
        // The theme is already updated in the session by the ThemeToggle component
        $this->currentTheme = Session::get('theme', 'light');
    }

    #[On('theme-changed')]
    public function handleThemeChanged($theme)
    {
        $this->currentTheme = $theme;
        // Force a re-render to apply the new theme
        $this->render();
    }

    public function render()
    {
        return view('livewire.slack-like-chat-component');
    }
}
