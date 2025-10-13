<?php

namespace App\Livewire;

use App\Events\MessageSent;
use App\Models\Channel;
use App\Models\Message;
use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Session;

class ChatComponent extends Component
{
    public $channels = [];
    public $selectedChannel = null;
    public $messages = [];
    public $newMessage = '';
    public $currentTheme = 'light';

    public function mount()
    {
        // Load current theme
        $this->currentTheme = Session::get('theme', 'light');
        
        // Load all channels the authenticated user is a member of
        $userChannels = auth()->user()->channels()->with('members')->get();
        $this->channels = $userChannels->toArray();
        
        // If channels exist, set the selectedChannel to the first one
        if (count($this->channels) > 0) {
            $this->selectedChannel = $this->channels[0];
            $this->loadMessages();
        }
    }

    public function selectChannel($channelId)
    {
        $channel = Channel::findOrFail($channelId);
        $this->selectedChannel = $channel->toArray();
        $this->loadMessages();
    }

    public function loadMessages()
    {
        if ($this->selectedChannel) {
            $channel = Channel::find($this->selectedChannel['id']);
            $messages = $channel->messages()
                ->with('user')
                ->orderBy('created_at', 'asc')
                ->get();
            
            // Convert messages to array and add avatar_url for each user
            $this->messages = $messages->map(function($message) {
                $messageArray = $message->toArray();
                $messageArray['user']['avatar_url'] = $message->user->avatar_url;
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

        if (!$this->selectedChannel) {
            return;
        }

        // Create the message
        $message = Message::create([
            'user_id' => auth()->id(),
            'channel_id' => $this->selectedChannel['id'],
            'body' => $this->newMessage,
        ]);

        // Load the user relationship
        $message->load('user');

        // Broadcast the message
        broadcast(new MessageSent($message))->toOthers();

        // Add message to the local messages array
        $messageArray = $message->toArray();
        $messageArray['user']['avatar_url'] = $message->user->avatar_url;
        $this->messages[] = $messageArray;

        // Reset the input
        $this->newMessage = '';

        // Dispatch browser event to scroll to bottom
        $this->dispatch('message-sent');
    }

    #[On('echo-private:channel.{selectedChannel.id},MessageSent')]
    public function handleNewMessage($payload)
    {
        if (!$this->selectedChannel) {
            return;
        }

        // Check if the message belongs to the current selected channel
        if ($payload['channel_id'] == $this->selectedChannel['id']) {
            // Create a message object from the payload
            $message = Message::find($payload['id']);
            
            if ($message) {
                $message->load('user');
                $messageArray = $message->toArray();
                $messageArray['user']['avatar_url'] = $message->user->avatar_url;
                $this->messages[] = $messageArray;
                $this->dispatch('message-received');
            }
        }
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
        return view('livewire.chat-component');
    }
}
