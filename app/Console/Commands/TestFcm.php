<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class TestFcm extends Command
{
    protected $signature = 'test:fcm {token?}';
    protected $description = 'Test FCM Notification';

    public function handle()
    {
        $this->info('Starting FCM Test...');

        // 1. Check Setting
        $setting = Setting::where('type', 'firebase_service_json_file')->first();
        if (!$setting) {
            $this->error('Setting "firebase_service_json_file" not found in DB.');
            return;
        }
        $fileName = $setting->data;
        $this->info("JSON Filename from DB: $fileName");

        // 2. Check File
        $filePath = public_path() . '/assets/' . $fileName;
        if (!file_exists($filePath)) {
            $this->error("File not found at: $filePath");
            return;
        }
        $this->info("File exists at: $filePath");

        // 3. Test Access Token
        $this->info('Attempting to get Access Token...');
        try {
            $accessToken = getAccessToken();
            if ($accessToken) {
                $this->info("Access Token retrieved successfully (Length: " . strlen($accessToken) . ")");
            } else {
                $this->error("Failed to retrieve Access Token.");
                return;
            }
        } catch (\Exception $e) {
            $this->error("Exception getting Access Token: " . $e->getMessage());
            return;
        }

        // 4. Send Notification
        $token = $this->argument('token');
        if (!$token) {
            $this->warn("No token provided. Skipping send test. (Usage: php artisan test:fcm <token>)");
            // Use a dummy token to test the REQUEST structure at least, expecting an error from Firebase
            $token = "dummy_token_for_testing_structure"; 
        }

        $this->info("Sending to token: $token");
        
        $fcmMsg = [
            'title' => 'Test Notification',
            'message' => 'This is a test message from artisan command',
            'body' => 'This is a test message from artisan command',
            'type' => 'test',
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            'sound' => 'default',
            'id' => '123'
        ];

        // We can't easily capture the return of send_push_notification because it yields promises/void, 
        // but we can check logs or modify it. 
        // However, send_push_notification in custom_helper uses Guzzle Pool and async requests.
        // It logs errors.
        
        send_push_notification([$token], $fcmMsg);
        
        $this->info("Notification send initiated. Check laravel.log for 'INVALID_ARGUMENT' or success details.");
    }
}
