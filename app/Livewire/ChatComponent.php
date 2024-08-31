<?php

namespace App\Livewire;

use App\Events\MessageSentEvent;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class ChatComponent extends Component
{
    public $user;
    public $senderId;
    public $receiverId;
    public $message = '';
    public $messages = [];

    public function render()
    {
        return view('livewire.chat-component');
    }

    public function mount($user_id)
    {
        $this->senderId = Auth::user()->id;
        $this->receiverId = $user_id;

        $messages = ChatMessage::query()
                ->select(['id', 'sender_id', 'receiver_id', 'message'])
                ->where(function($query) {
                    $query->where('sender_id', $this->senderId)
                            ->where('receiver_id', $this->receiverId);
                })->orWhere(function($query){
                    $query->where('sender_id', $this->receiverId)
                            ->where('receiver_id', $this->senderId);
                })
                ->with([
                    'sender' => fn($q) => $q->select(['id', 'name']),
                    'receiver' => fn($q) => $q->select(['id', 'name']),
                ])
                ->get();
        foreach ($messages as $message) {
            $this->appendMessage($message);
        }

        $this->user = User::findOrFail($user_id);
    }

    public function sendChatMessage()
    {
        $chatMessage = new ChatMessage();
        $chatMessage->sender_id = $this->senderId;
        $chatMessage->receiver_id = $this->receiverId;
        $chatMessage->message = $this->message;
        $chatMessage->save();


        $this->appendMessage($chatMessage);

        broadcast(new MessageSentEvent($chatMessage))->toOthers();

        $this->message = '';
    }


    #[On('echo-private:chat-channel.{senderId},MessageSentEvent')]

    public function listenMessageList($event)
    {
        $chatMessage = ChatMessage::query()
                ->with([
                    'sender' => fn($q) => $q->select(['id', 'name']),
                    'receiver' => fn($q) => $q->select(['id', 'name']),
                ])
                ->findOrFail($event['message']['id']);

        $this->appendMessage($chatMessage);
    }

    public function appendMessage($message)
    {
        $this->messages[] = [
            'id' => $message->id,
            'message' => $message->message,
            'sender' => $message->sender?->name,
            'receiver' => $message->receiver?->name,
        ];
    }
}
