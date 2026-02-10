<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;
use App\Models\Usertokens;
use App\Models\Chats;
use Illuminate\Support\Facades\Log;

class TestChatFcm extends Command
{
    protected $signature = 'test:chat-fcm {user_id} {message=Hello from Artisan} {--insert-db : Insert message into DB}';
    protected $description = 'Test Chat FCM Notification by User ID';

    public function handle()
    {
        $this->info('Starting Chat FCM Test...');
        $userId = $this->argument('user_id');
        $message = $this->argument('message');
        $insertDb = $this->option('insert-db');

        // Fetch tokens for the user
        $tokens = Usertokens::where('customer_id', $userId)->pluck('fcm_id')->toArray();

        if (empty($tokens)) {
            $this->error("No FCM tokens found for user ID: $userId");
            // If strictly testing DB logic, maybe we continue? But notification needs token.
            if (!$insertDb) return;
        }

        if ($insertDb) {
             $this->info("Inserting message into DB...");
             Chats::create([
                 'sender_id' => 1, // Dummy admin/sender
                 'receiver_id' => $userId,
                 'property_id' => 1,
                 'message' => $message,
                 'is_read' => 0,
                 'created_at' => now(),
                 'updated_at' => now(),
                 'chat_message_type' => 'text'
             ]);
             $this->info("Message inserted.");
        }

        $this->info("Found " . count($tokens) . " tokens for user $userId. Sending message: '$message'");
        
        $fcmMsg = [
            'title' => 'New Message',
            'message' => $message,
            'type' => 'chat',
            'body' => $message,
            'sender_id' => '1', // Dummy sender ID
            'receiver_id' => $userId,
            'username' => 'Test User',
            'file' => '',
            'audio' => '',
            'date' => now()->toDateTimeString(),
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'sound' => 'default',
            'property_id' => '1', // Dummy property ID
            'property_title_image' => '',
            'property_title' => 'Test Property',
            'chat_message_type' => 'text',
            'user_profile' => ''
        ];

        send_push_notification($tokens, $fcmMsg);
        
        $this->info("Chat Notification sent to " . count($tokens) . " devices.");
    }
}
