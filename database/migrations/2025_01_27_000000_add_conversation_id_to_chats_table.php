<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add conversation_id column to chats table
        if (!Schema::hasColumn('chats', 'conversation_id')) {
            Schema::table('chats', function (Blueprint $table) {
                $table->string('conversation_id', 100)->nullable()->after('property_id')->comment('Unique conversation identifier');
                $table->index('conversation_id');
            });
        }

        // Generate conversation_id for existing chats
        $this->generateConversationIdsForExistingChats();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('chats', 'conversation_id')) {
            Schema::table('chats', function (Blueprint $table) {
                $table->dropIndex(['conversation_id']);
                $table->dropColumn('conversation_id');
            });
        }
    }

    /**
     * Generate conversation IDs for existing chats
     */
    private function generateConversationIdsForExistingChats(): void
    {
        // Get all unique combinations of sender_id, receiver_id, and property_id
        $conversations = DB::table('chats')
            ->select('sender_id', 'receiver_id', 'property_id')
            ->distinct()
            ->get();

        foreach ($conversations as $conversation) {
            // Generate a unique conversation ID
            $conversationId = $this->generateConversationId(
                $conversation->sender_id,
                $conversation->receiver_id,
                $conversation->property_id
            );

            // Update all chats with this combination
            DB::table('chats')
                ->where('sender_id', $conversation->sender_id)
                ->where('receiver_id', $conversation->receiver_id)
                ->where('property_id', $conversation->property_id)
                ->update(['conversation_id' => $conversationId]);
        }
    }

    /**
     * Generate a unique conversation ID
     */
    private function generateConversationId(int $senderId, int $receiverId, int $propertyId): string
    {
        // Create a consistent conversation ID by sorting the user IDs
        $userIds = [$senderId, $receiverId];
        sort($userIds);
        
        return "conv_{$userIds[0]}_{$userIds[1]}_prop_{$propertyId}";
    }
};
