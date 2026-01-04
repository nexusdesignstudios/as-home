<?php

namespace App\Console\Commands;

use App\Models\Property;
use Illuminate\Console\Command;

class ListHotels extends Command
{
    protected $signature = 'list:hotels';
    protected $description = 'List all hotels with their IDs';

    public function handle()
    {
        $hotels = Property::where('property_classification', 5)
            ->where('request_status', 'approved')
            ->where('status', 1)
            ->get(['id', 'title']);
        
        $this->info("📋 Available Hotels:");
        foreach ($hotels as $hotel) {
            $this->line("  ID: {$hotel->id} - {$hotel->title}");
        }
        
        return 0;
    }
}
