<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PropertySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Check if property_classification column exists
        $hasClassificationColumn = Schema::hasColumn('propertys', 'property_classification');

        // Get the first category ID or use a default one
        $category = DB::table('categories')->first();
        $categoryId = $category ? $category->id : 1;

        // Sample property data with different classifications
        $properties = [
            // Sell/Long Term Rent properties
            [
                'category_id' => $categoryId,
                'title' => 'Modern Apartment for Sale',
                'slug_id' => 'modern-apartment-for-sale',
                'description' => 'Beautiful modern apartment with 3 bedrooms and 2 bathrooms in a prime location.',
                'address' => '123 Main Street, New York, NY',
                'client_address' => '123 Main Street, New York, NY',
                'propery_type' => 0, // Sell
                'property_classification' => 1, // Sell/Long Term Rent
                'price' => '250000',
                'city' => 'New York',
                'country' => 'USA',
                'state' => 'NY',
                'status' => 1, // Active
                'request_status' => 'approved',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'is_premium' => 0
            ],
            [
                'category_id' => $categoryId,
                'title' => 'Luxury Apartment for Rent',
                'slug_id' => 'luxury-apartment-for-rent',
                'description' => 'Spacious luxury apartment with high-end finishes and amenities.',
                'address' => '456 Park Avenue, New York, NY',
                'client_address' => '456 Park Avenue, New York, NY',
                'propery_type' => 1, // Rent
                'property_classification' => 1, // Sell/Long Term Rent
                'price' => '3500',
                'city' => 'New York',
                'country' => 'USA',
                'state' => 'NY',
                'status' => 1, // Active
                'request_status' => 'approved',
                'latitude' => 40.7128,
                'longitude' => -74.0060,
                'rentduration' => 'Monthly',
                'is_premium' => 0
            ],

            // Commercial properties
            [
                'category_id' => $categoryId,
                'title' => 'Office Space for Rent',
                'slug_id' => 'office-space-for-rent',
                'description' => 'Prime office space in downtown business district with parking.',
                'address' => '789 Business Blvd, Chicago, IL',
                'client_address' => '789 Business Blvd, Chicago, IL',
                'propery_type' => 1, // Rent
                'property_classification' => 2, // Commercial
                'price' => '5000',
                'city' => 'Chicago',
                'country' => 'USA',
                'state' => 'IL',
                'status' => 1, // Active
                'request_status' => 'approved',
                'latitude' => 41.8781,
                'longitude' => -87.6298,
                'rentduration' => 'Monthly',
                'is_premium' => 1
            ],
            [
                'category_id' => $categoryId,
                'title' => 'Retail Space for Sale',
                'slug_id' => 'retail-space-for-sale',
                'description' => 'High-traffic retail space in shopping district with excellent visibility.',
                'address' => '101 Market Street, San Francisco, CA',
                'client_address' => '101 Market Street, San Francisco, CA',
                'propery_type' => 0, // Sell
                'property_classification' => 2, // Commercial
                'price' => '750000',
                'city' => 'San Francisco',
                'country' => 'USA',
                'state' => 'CA',
                'status' => 1, // Active
                'request_status' => 'approved',
                'latitude' => 37.7749,
                'longitude' => -122.4194,
                'is_premium' => 0
            ],

            // New Project properties
            [
                'category_id' => $categoryId,
                'title' => 'Riverside Condos - New Development',
                'slug_id' => 'riverside-condos-new-development',
                'description' => 'Brand new development with luxury condos overlooking the river. Pre-construction pricing available.',
                'address' => '555 Riverside Drive, Austin, TX',
                'client_address' => '555 Riverside Drive, Austin, TX',
                'propery_type' => 0, // Sell
                'property_classification' => 3, // New Project
                'price' => '350000',
                'city' => 'Austin',
                'country' => 'USA',
                'state' => 'TX',
                'status' => 1, // Active
                'request_status' => 'approved',
                'latitude' => 30.2672,
                'longitude' => -97.7431,
                'is_premium' => 1
            ],
            [
                'category_id' => $categoryId,
                'title' => 'Skyline Towers - Coming Soon',
                'slug_id' => 'skyline-towers-coming-soon',
                'description' => 'New high-rise development with panoramic city views. Modern amenities and premium finishes.',
                'address' => '777 Downtown Ave, Miami, FL',
                'client_address' => '777 Downtown Ave, Miami, FL',
                'propery_type' => 0, // Sell
                'property_classification' => 3, // New Project
                'price' => '425000',
                'city' => 'Miami',
                'country' => 'USA',
                'state' => 'FL',
                'status' => 1, // Active
                'request_status' => 'approved',
                'latitude' => 25.7617,
                'longitude' => -80.1918,
                'is_premium' => 0
            ],

            // Vacation Homes
            [
                'category_id' => $categoryId,
                'title' => 'Beachfront Villa for Rent',
                'slug_id' => 'beachfront-villa-for-rent',
                'description' => 'Luxurious beachfront villa with private pool and direct beach access. Perfect for vacations.',
                'address' => '222 Ocean Drive, Malibu, CA',
                'client_address' => '222 Ocean Drive, Malibu, CA',
                'propery_type' => 1, // Rent
                'property_classification' => 4, // Vacation Homes
                'price' => '1200',
                'city' => 'Malibu',
                'country' => 'USA',
                'state' => 'CA',
                'status' => 1, // Active
                'request_status' => 'approved',
                'latitude' => 34.0259,
                'longitude' => -118.7798,
                'rentduration' => 'Daily',
                'is_premium' => 1
            ],
            [
                'category_id' => $categoryId,
                'title' => 'Mountain Cabin Retreat',
                'slug_id' => 'mountain-cabin-retreat',
                'description' => 'Cozy mountain cabin with stunning views and hiking trails nearby. Perfect for weekend getaways.',
                'address' => '333 Mountain Road, Aspen, CO',
                'client_address' => '333 Mountain Road, Aspen, CO',
                'propery_type' => 1, // Rent
                'property_classification' => 4, // Vacation Homes
                'price' => '250',
                'city' => 'Aspen',
                'country' => 'USA',
                'state' => 'CO',
                'status' => 1, // Active
                'request_status' => 'approved',
                'latitude' => 39.1911,
                'longitude' => -106.8175,
                'rentduration' => 'Daily',
                'is_premium' => 0
            ],

            // Hotel Booking
            [
                'category_id' => $categoryId,
                'title' => 'Luxury Hotel Suite',
                'slug_id' => 'luxury-hotel-suite',
                'description' => 'Elegant hotel suite with 5-star amenities, room service, and spa access.',
                'address' => '888 Boulevard, Las Vegas, NV',
                'client_address' => '888 Boulevard, Las Vegas, NV',
                'propery_type' => 1, // Rent
                'property_classification' => 5, // Hotel Booking
                'price' => '299',
                'city' => 'Las Vegas',
                'country' => 'USA',
                'state' => 'NV',
                'status' => 1, // Active
                'request_status' => 'approved',
                'latitude' => 36.1699,
                'longitude' => -115.1398,
                'rentduration' => 'Daily',
                'is_premium' => 1
            ],
            [
                'category_id' => $categoryId,
                'title' => 'Boutique Hotel Room',
                'slug_id' => 'boutique-hotel-room',
                'description' => 'Charming boutique hotel room in the heart of the historic district. Walking distance to attractions.',
                'address' => '444 Main Street, New Orleans, LA',
                'client_address' => '444 Main Street, New Orleans, LA',
                'propery_type' => 1, // Rent
                'property_classification' => 5, // Hotel Booking
                'price' => '189',
                'city' => 'New Orleans',
                'country' => 'USA',
                'state' => 'LA',
                'status' => 1, // Active
                'request_status' => 'approved',
                'latitude' => 29.9511,
                'longitude' => -90.0715,
                'rentduration' => 'Daily',
                'is_premium' => 0
            ],
        ];

        // Insert properties
        foreach ($properties as $property) {
            // Add created_at and updated_at
            $property['created_at'] = now();
            $property['updated_at'] = now();
            $property['title_image'] = 'title_image.jpg'; // Default image
            $property['total_click'] = rand(10, 100);

            // Remove property_classification if column doesn't exist
            if (!$hasClassificationColumn && isset($property['property_classification'])) {
                unset($property['property_classification']);
            }

            DB::table('propertys')->insert($property);
        }
    }
}
