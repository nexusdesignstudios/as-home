<?php

namespace App\Console\Commands;

use App\Models\Property;
use App\Http\Controllers\ApiController;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DebugAreaFilter extends Command
{
    protected $signature = 'debug:area-filter {minArea} {maxArea}';
    protected $description = 'Debug area filtering with SQL and raw parameter inspection';

    public function handle()
    {
        $minArea = $this->argument('minArea');
        $maxArea = $this->argument('maxArea');

        $this->info("🔍 Debugging Area Filter: min={$minArea}, max={$maxArea}");
        $this->info("================================================");

        // Enable query log
        DB::enableQueryLog();

        // ---------- Test 1: get-property-list ----------
        $this->info("\n📋 1) get-property-list (non-hotels)");
        $this->info("-------------------------------------------");

        $request = new Request([
            'min_area' => $minArea,
            'max_area' => $maxArea,
            'limit' => 10,
            'offset' => 0,
        ]);

        $controller = new ApiController();
        $response = $controller->getPropertyList($request);
        $data = $response->getData(true);

        $queries = DB::getQueryLog();
        DB::flushQueryLog();

        $this->info("Total returned: " . ($data['total'] ?? 0));
        $this->info("SQL queries run: " . count($queries));
        foreach ($queries as $i => $q) {
            $this->info("  Query {$i}:");
            $this->info("    " . $q['query']);
            $this->info("    Bindings: " . json_encode($q['bindings']));
        }

        if (!empty($data['data'])) {
            $this->info("\nResults and their area values:");
            foreach ($data['data'] as $i => $prop) {
                $size = $this->extractArea($prop);
                $within = $this->isWithinRange($size, $minArea, $maxArea);
                $this->line("  [{$i}] ID:{$prop['id']} | Size: {$size} | Within: " . ($within ? 'YES' : 'NO'));
            }
        } else {
            $this->info("  No results.");
        }

        // ---------- Test 2: Raw assign_parameters check ----------
        $this->info("\n🔎 2) Raw assign_parameters inspection");
        $this->info("-------------------------------------------");

        $params = DB::table('assign_parameters')
            ->join('parameters', 'assign_parameters.parameter_id', '=', 'parameters.id')
            ->select('assign_parameters.property_id', 'parameters.name', 'assign_parameters.value')
            ->where(function ($q) {
                $q->where('parameters.name', 'LIKE', '%area%')
                  ->orWhere('parameters.name', 'LIKE', '%size%')
                  ->orWhere('parameters.name', 'LIKE', '%sqm%')
                  ->orWhere('parameters.name', 'LIKE', '%sqft%')
                  ->orWhere('parameters.name', 'LIKE', '%square%');
            })
            ->limit(20)
            ->get();

        $this->info("Sample assign_parameters records (area/size related):");
        foreach ($params as $p) {
            $this->line("  Property {$p->property_id} | {$p->name} = {$p->value}");
        }

        $this->info("\n✅ Debug complete.");
    }

    private function extractArea($prop)
    {
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
