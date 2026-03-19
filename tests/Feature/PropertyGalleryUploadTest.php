<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Property;
use App\Models\PropertyImages;
use App\Models\Category;
use Illuminate\Support\Facades\Schema;

use Illuminate\Support\Facades\Log;

class PropertyGalleryUploadTest extends TestCase
{
    // use RefreshDatabase; // We won't use RefreshDatabase to avoid wiping the existing DB, we'll clean up manually

    public function test_admin_can_upload_multiple_gallery_images()
    {
        Log::shouldReceive('info')->withAnyArgs()->andReturnNull();
        Log::shouldReceive('error')->withAnyArgs()->andReturnNull();
        Log::shouldReceive('warning')->withAnyArgs()->andReturnNull();
        Log::shouldReceive('debug')->withAnyArgs()->andReturnNull();
        // 1. Authenticate as Admin
        // Assuming user ID 1 is an admin. If not, we might need to find an admin user.
        $admin = User::where('type', 0)->first(); // Explicitly get an admin
        if (!$admin) {
             $admin = User::find(1); // Fallback
        }
        if (!$admin) {
            $this->markTestSkipped('Admin user not found.');
        }
        $this->actingAs($admin);

        // 2. Find an existing property that is NOT added by admin (to test admin edit on user property)
        // or create one
        $property = Property::where('added_by', '!=', 0)->first();
        if (!$property) {
             // Create a dummy category if needed
             $category = Category::first();
             if (!$category) {
                 $category = Category::create(['category' => 'Test Category', 'status' => '1', 'parameter_types' => '']);
             }
             
             $property = Property::create([
                'title' => 'Test User Property',
                'description' => 'Test Description',
                'category_id' => $category->id,
                'address' => 'Test Address',
                'price' => 100,
                'propery_type' => 0, // Sell
                'added_by' => 999, // Simulate user ID
             ]);
        }
        $category = Category::first();
        if (!$category) {
            $category = Category::create(['category' => 'Test Category', 'status' => '1', 'parameter_types' => '']);
        }
        if (empty($property->title)) {
            $property->title = 'Test User Property';
        }
        if (empty($property->description)) {
            $property->description = 'Test Description';
        }
        if (empty($property->address)) {
            $property->address = 'Test Address';
        }
        if (empty($property->category_id)) {
            $property->category_id = $category->id;
        }
        if (empty($property->price)) {
            $property->price = 100;
        }
        if ($property->added_by == 0) {
            $property->added_by = 999;
        }
        $property->save();

        $propertyId = $property->id;

        // 3. Fake Storage
        // We will mock the public disk where images are stored
        Storage::fake('public_images'); 
        // Note: The controller uses public_path(), which is hard to mock directly with Storage::fake 
        // without changing the controller code.
        // However, for this test, we want to verify the CONTROLLER LOGIC iterates correctly.
        // So we will just simulate the request and check the Database entries.
        // We won't actually check if files exist on disk to avoid messing with real files, 
        // but since the controller uses `move`, it might try to write to disk.
        // To be safe, we can use a try-catch block or accept that it writes to the real path 
        // (and we clean up if possible, or use dummy files).
        
        // Let's create dummy files
        $file1 = UploadedFile::fake()->image('gallery1.jpg');
        $file2 = UploadedFile::fake()->image('gallery2.jpg');
        $file3 = UploadedFile::fake()->image('gallery3.jpg');

        // 4. Send PUT/PATCH request to update property
        // The route is 'property.update'
        $response = $this->post(route('property.update', $propertyId), [
            '_method' => 'PATCH',
            'title' => $property->title, // Required fields
            'description' => $property->description,
            'category' => $property->category_id,
            'address' => $property->address,
            'price' => $property->price,
            'property_type' => 1, // Rent
            'price_duration' => 'Daily', // Required for rent
            'category' => $property->category_id,

            // Add other required fields if validation fails
            'latitude' => '0',
            'longitude' => '0',
            // 'gallery_images' => [$file1, $file2, $file3], // Multiple images
            // Try sending files individually to simulate how HTML forms might send array inputs if handled manually, 
            // but usually Laravel test handles array of files correctly.
            // Let's verify if the key matches. The controller expects 'gallery_images'.
            // Maybe FilePond sends them differently?
            // But here we are testing the CONTROLLER, not FilePond.
            // The controller expects $request->file('gallery_images') to be an array.
            
            // Let's try explicitly as array
            'gallery_images' => [
                $file1,
                $file2,
                $file3
            ]
        ]);

        // 5. Assertions
        // Check for redirect (success)
        if ($response->status() !== 302) {
             dump($response->getContent());
        }
        
        if (session('errors')) {
            dump(session('errors')->all());
        }

        // Check if ANY property images exist at all
        $allImages = PropertyImages::count();
        // dump("Total PropertyImages in DB: " . $allImages);

        $response->assertStatus(302);

        // Check Database
        // We expect 3 new entries in PropertyImages table for this property
        // We need to count how many images were there before?
        // Actually, we can just check if we can find entries created *just now* or check the total count increase.
        
        // Let's check if we can find 3 images associated with this property that were created recently
        $recentImages = PropertyImages::where('propertys_id', $propertyId)
                                    ->where('created_at', '>=', now()->subSeconds(10))
                                    ->count();

        $this->assertEquals(3, $recentImages, "Expected 3 gallery images to be saved, found $recentImages.");
        
        echo "\nTest Passed: 3 gallery images were successfully saved to the database.\n";
    }
}
