<?php

namespace App\Events;

use App\Models\DirectMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DirectMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $directMessage;

    /**
     * Create a new event instance.
     */
    public function __construct(DirectMessage $directMessage)
    {
        $this->directMessage = $directMessage;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->directMessage->receiver_id),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->directMessage->id,
            'sender_id' => $this->directMessage->sender_id,
            'receiver_id' => $this->directMessage->receiver_id,
            'message' => $this->directMessage->message,
            'is_read' => $this->directMessage->is_read,
            'created_at' => $this->directMessage->created_at->toISOString(),
            'sender' => [
                'id' => $this->directMessage->sender->id,
                'name' => $this->directMessage->sender->name,
                'avatar_url' => $this->directMessage->sender->avatar_url,
            ],
        ];
    }
}
