<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Usertokens;
use Illuminate\Support\Facades\DB;

class ListFcmUsers extends Command
{
    protected $signature = 'list:fcm-users';
    protected $description = 'List users with valid FCM tokens';

    public function handle()
    {
        $this->info('Searching for users with FCM tokens...');

        $users = Usertokens::select('customer_id', DB::raw('count(*) as token_count'))
            ->groupBy('customer_id')
            ->orderBy('token_count', 'desc')
            ->limit(10)
            ->get();

        if ($users->isEmpty()) {
            $this->error('No users found with FCM tokens.');
            return;
        }

        $this->table(
            ['Customer ID', 'Token Count'],
            $users->map(function ($user) {
                return [
                    'Customer ID' => $user->customer_id,
                    'Token Count' => $user->token_count,
                ];
            })
        );
    }
}
