<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    // Kênh phát tin nhắn (có thể thay đổi thành PresenceChannel hoặc PrivateChannel nếu cần)
    public function broadcastOn()
    {
        return new PrivateChannel('chat');
    }

    // Định dạng dữ liệu sẽ được phát đi
    public function broadcastWith()
    {
        return ['message' => $this->message];
    }
}
