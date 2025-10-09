<?php

namespace App\Livewire;

use App\Events\DirectMessageSent;
use App\Models\DirectMessage;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\On;

class SlackLikeChatComponent extends Component
{
    public $users = [];
    public $selectedUser = null;
    public $messages = [];
    public $newMessage = '';
    public $conversations = [];
    public $showUserList = true;
    
    protected $listeners = ['refreshComponent'];

    public function mount()
    {
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

        // Reload conversations to update last message
        $this->loadConversations();

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
        // Reload users and conversations when a new message is received
        $this->loadUsers();
        $this->loadConversations();
        if ($this->selectedUser) {
            $this->loadMessages();
        }
    }

    public function render()
    {
        return view('livewire.slack-like-chat-component');
    }
}
