<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Property;
use App\Models\InterestedUser;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class NPlusOneQueryTest extends TestCase
{
    /**
     * Test that property list query doesn't have N+1 queries
     */
    public function test_property_list_query_optimization()
    {
        // Enable query logging
        DB::enableQueryLog();
        
        // Test the optimized query directly
        $properties = Property::with('category')
            ->with('customer:id,name,mobile')
            ->with('assignParameter.parameter')
            ->with('interested_users.customer:id,name,email,mobile')
            ->with('documents')
            ->with('gallery')
            ->with('advertisement')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();
        
        // Get the queries that were executed
        $queries = DB::getQueryLog();
        
        // Count queries by type
        $selectQueries = array_filter($queries, function($query) {
            return stripos($query['query'], 'select') === 0;
        });
        
        // Should have around 7-8 queries for eager loading
        $this->assertLessThan(15, count($selectQueries), 
            "Too many SELECT queries detected. Possible N+1 query issue. Found " . count($selectQueries) . " queries.");
        
        // Test that we can access relationships without additional queries
        DB::flushQueryLog();
        
        foreach ($properties as $property) {
            // These should not trigger additional queries due to eager loading
            $category = $property->category;
            $customer = $property->customer;
            $interestedUsers = $property->interested_users;
            $documents = $property->documents;
            $gallery = $property->gallery;
            $advertisement = $property->advertisement;
            
            // Access interested user customers - should not trigger additional queries
            foreach ($interestedUsers as $interestedUser) {
                $interestedCustomer = $interestedUser->customer;
            }
        }
        
        $additionalQueries = DB::getQueryLog();
        
        // Should have minimal additional queries
        $this->assertLessThan(5, count($additionalQueries), 
            "Too many additional queries when accessing relationships. Found " . count($additionalQueries) . " queries.");
        
        DB::disableQueryLog();
    }
}