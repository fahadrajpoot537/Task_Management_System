<?php

namespace App\Livewire;

use App\Events\DirectMessageSent;
use App\Models\DirectMessage;
use App\Models\User;
use Livewire\Component;

class PrivateChatComponent extends Component
{
    public $conversations = [];
    public $selectedUser = null;
    public $messages = [];
    public $newMessage = '';
    public $users = [];

    public function mount()
    {
        // Load conversations for the authenticated user
        $this->loadConversations();
        
        // Load all users except current user
        $this->users = User::where('id', '!=', auth()->id())
            ->select('id', 'name', 'email', 'avatar')
            ->get()
            ->toArray();
    }

    public function loadConversations()
    {
        $conversations = auth()->user()->conversations();
        $this->conversations = $conversations->map(function ($conversation) {
            return [
                'user' => $conversation['user']->toArray(),
                'last_message' => $conversation['last_message']->toArray(),
                'unread_count' => $conversation['unread_count']
            ];
        })->toArray();
    }

    public function selectUser($userId)
    {
        $user = User::findOrFail($userId);
        $this->selectedUser = $user->toArray();
        $this->loadMessages();
        
        // Mark messages as read
        $this->markMessagesAsRead();
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
            
            // Reload conversations to update unread count
            $this->loadConversations();
        }
    }

    public function refreshComponent()
    {
        // Reload conversations and messages when a new message is received
        $this->loadConversations();
        if ($this->selectedUser) {
            $this->loadMessages();
        }
    }

    public function render()
    {
        return view('livewire.private-chat-component');
    }
}
