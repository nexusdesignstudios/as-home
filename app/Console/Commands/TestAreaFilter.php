<?php

namespace App\Console\Commands;

use App\Models\Property;
use App\Http\Controllers\ApiController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class TestAreaFilter extends Command
{
    protected $signature = 'test:area-filter {minArea} {maxArea}';
    protected $description = 'Test area filtering on get-property-list and search-hotels-with-dates endpoints';

    public function handle()
    {
        $minArea = $this->argument('minArea');
        $maxArea = $this->argument('maxArea');

        $this->info("🔍 Testing Area Filter: min={$minArea}, max={$maxArea}");
        $this->info("================================================");

        // ---------- Test 1: Regular Properties (get-property-list) ----------
        $this->info("\n📋 1) get-property-list (non-hotels)");
        $this->info("-------------------------------------------");

        $request = new Request([
            'min_area' => $minArea,
            'max_area' => $maxArea,
            'limit' => 10,
            'offset' => 0,
        ]);

        $controller = new ApiController();
        $responseRegular = $controller->getPropertyList($request);
        $dataRegular = $responseRegular->getData(true);

        $this->info("Total returned: " . ($dataRegular['total'] ?? 0));
        if (!empty($dataRegular['data'])) {
            foreach ($dataRegular['data'] as $i => $prop) {
                $size = $this->extractArea($prop);
                $within = $this->isWithinRange($size, $minArea, $maxArea);
                $this->line("  [{$i}] ID:{$prop['id']} | Size: {$size} | Within: " . ($within ? 'YES' : 'NO'));
            }
        } else {
            $this->info("  No results.");
        }

        // ---------- Test 2: Hotels (search-hotels-with-dates) ----------
        $this->info("\n🏨 2) search-hotels-with-dates (hotels)");
        $this->info("-------------------------------------------");

        $requestHotel = new Request([
            'check_in_date' => now()->addDays(1)->format('Y-m-d'),
            'check_out_date' => now()->addDays(3)->format('Y-m-d'),
            'min_area' => $minArea,
            'max_area' => $maxArea,
            'limit' => 10,
            'offset' => 0,
        ]);

        $responseHotel = $controller->searchHotelsWithDates($requestHotel);
        $dataHotel = $responseHotel->getData(true);

        if (isset($dataHotel['error']) && $dataHotel['error']) {
            $this->error("  API Error: " . $dataHotel['message']);
        } else {
            $this->info("Total returned: " . ($dataHotel['total'] ?? 0));
            if (!empty($dataHotel['data'])) {
                foreach ($dataHotel['data'] as $i => $prop) {
                    $size = $this->extractArea($prop);
                    $within = $this->isWithinRange($size, $minArea, $maxArea);
                    $this->line("  [{$i}] ID:{$prop['id']} | Size: {$size} | Within: " . ($within ? 'YES' : 'NO'));
                }
            } else {
                $this->info("  No results.");
            }
        }

        $this->info("\n✅ Test complete.");
    }

    private function extractArea($prop)
    {
        // Try to read from assign_parameters (like the backend filter does)
        if (!empty($prop['assign_parameter'])) {
            foreach ($prop['assign_parameter'] as $ap) {
                $name = strtolower($ap['parameter']['name'] ?? '');
                $value = $ap['value'] ?? '';
                if (
                    (str_contains($name, 'area') || str_contains($name, 'size') || str_contains($name, 'sqm') || str_contains($name, 'sqft')) &&
                    is_numeric($value)
                ) {
                    return $value;
                }
            }
        }
        // Fallback: try a direct 'size' field if present
        return $prop['size'] ?? 'N/A';
    }

    private function isWithinRange($size, $min, $max)
    {
        if (!is_numeric($size)) return false;
        $size = (float)$size;
        $min = is_numeric($min) ? (float)$min : 0;
        $max = is_numeric($max) ? (float)$max : INF;
        return $size >= $min && $size <= $max;
    }
}
