<div>
    <div class="chat-box">
        <!-- Vòng lặp hiển thị các tin nhắn -->
        @foreach($messages as $message)
        <div class="message">
            <strong>{{ $message->user->name }}:</strong> {{ $message->content }}
        </div>
        @endforeach
    </div>

    <!-- Form gửi tin nhắn -->
    <div class="send-message">
        <input type="text" wire:model="newMessage" placeholder="Nhập tin nhắn..." />
        <button wire:click="sendMessage">Gửi</button>
    </div>

    <!-- Tự động cuộn xuống khi có tin nhắn mới -->
    <script>
        window.addEventListener('livewire:load', function() {
            Echo.private('chat')
                .listen('MessageSent', (e) => {
                    Livewire.emit('messageReceived', e.message);
                });
        });
    </script>
</div>

<style>
    .chat-box {
        height: 300px;
        overflow-y: scroll;
        border: 1px solid #ccc;
        padding: 10px;
        margin-bottom: 10px;
    }
    .message {
        margin-bottom: 10px;
    }
    .send-message {
        display: flex;
        gap: 10px;
    }
    .send-message input {
        flex: 1;
    }
</style>
