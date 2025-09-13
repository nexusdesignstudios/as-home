<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use App\Traits\HasAppTimezone;

class Chats extends Model
{
    use HasFactory, HasAppTimezone;
    protected $dates = ['created_at', 'updated_at'];
    protected $table = 'chats';
    protected $fillable = ['sender_id', 'receiver_id', 'property_id', 'message', 'is_read', 'approval_status', 'file', 'audio', 'created_at', 'updated_at'];


    protected static function boot()
    {
        parent::boot();
        static::deleting(static function ($chat) {
            if (collect($chat)->isNotEmpty()) {
                // Check if using S3
                $disk = env('FILESYSTEM_DISK', config('filesystems.default', 'local'));

                // Delete File
                if ($chat->getRawOriginal('file') != '') {
                    $file = $chat->getRawOriginal('file');

                    if ($disk === 's3') {
                        // Delete from S3
                        $relativeDir = 'images/' . trim(config('global.CHAT_FILE'), '/');
                        $s3Key = $relativeDir . '/' . $file;
                        try {
                            \Illuminate\Support\Facades\Storage::disk('s3')->delete($s3Key);
                            \Illuminate\Support\Facades\Log::info('Chat file deleted from S3: ' . $s3Key);
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Failed to delete chat file from S3: ' . $e->getMessage());
                        }
                    } else {
                        // Fallback to local deletion
                        if (file_exists(public_path('images') . config('global.CHAT_FILE') . $file)) {
                            unlink(public_path('images') . config('global.CHAT_FILE') . $file);
                        }
                    }
                }

                // Delete Audio
                if ($chat->getRawOriginal('audio') != '') {
                    $audio = $chat->getRawOriginal('audio');

                    if ($disk === 's3') {
                        // Delete from S3
                        $relativeDir = 'images/' . trim(config('global.CHAT_FILE'), '/') . '/chat_audio';
                        $s3Key = $relativeDir . '/' . $audio;
                        try {
                            \Illuminate\Support\Facades\Storage::disk('s3')->delete($s3Key);
                            \Illuminate\Support\Facades\Log::info('Chat audio deleted from S3: ' . $s3Key);
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Failed to delete chat audio from S3: ' . $e->getMessage());
                        }
                    } else {
                        // Fallback to local deletion
                        if (file_exists(public_path('images') . config('global.CHAT_AUDIO') . $audio)) {
                            unlink(public_path('images') . config('global.CHAT_AUDIO') . $audio);
                        }
                    }
                }
            }
        });
    }

    public function sender()
    {
        return $this->belongsTo(Customer::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(Customer::class, 'receiver_id');
    }
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
    public function getFileAttribute($file)
    {
        if (empty($file)) {
            return '';
        }

        // Check if using S3
        $disk = env('FILESYSTEM_DISK', config('filesystems.default', 'local'));

        if ($disk === 's3') {
            // Get S3 URL - AWS_URL already includes the bucket name in the URL
            $s3Url = env('AWS_URL');
            $relativeDir = 'images/' . trim(config('global.CHAT_FILE'), '/');

            // Return full S3 URL without duplicating the bucket name
            return $s3Url . '/' . $relativeDir . '/' . $file;
        } else {
            // Fallback to local path
            return url('') . config('global.IMG_PATH') . config('global.CHAT_FILE') . $file;
        }
    }
    public function getAudioAttribute($value)
    {
        if (empty($value)) {
            return '';
        }

        // Check if using S3
        $disk = env('FILESYSTEM_DISK', config('filesystems.default', 'local'));

        if ($disk === 's3') {
            // Get S3 URL - AWS_URL already includes the bucket name in the URL
            $s3Url = env('AWS_URL');
            $relativeDir = 'images/' . trim(config('global.CHAT_FILE'), '/') . '/chat_audio';

            // Return full S3 URL without duplicating the bucket name
            return $s3Url . '/' . $relativeDir . '/' . $value;
        } else {
            // Fallback to local path
            return url('') . config('global.IMG_PATH') . config('global.CHAT_AUDIO') . $value;
        }
    }

    public function setMessageAttribute($value)
    {
        $this->attributes['message'] = htmlspecialchars($value);
    }

    public function getMessageAttribute($value)
    {
        // e() functions is used to print message in plain text
        return e(htmlspecialchars_decode($value));
    }
}
