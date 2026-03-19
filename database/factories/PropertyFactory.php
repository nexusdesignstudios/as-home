<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropertyFactory extends Factory
{
    public function definition(): array
    {
        $category = Category::query()->first();
        if (! $category) {
            $category = Category::create([
                'category' => 'Test Category',
                'image' => '',
                'status' => '1',
                'sequence' => 1,
                'parameter_types' => '',
                'property_classification' => 4,
                'slug_id' => 'test-category',
            ]);
        }

        $owner = Customer::query()->first();
        if (! $owner) {
            $owner = Customer::factory()->create();
        }

        return [
            'category_id' => $category->id,
            'title' => 'Test Property ' . $this->faker->unique()->word(),
            'title_ar' => null,
            'description' => $this->faker->paragraph(),
            'description_ar' => null,
            'area_description' => null,
            'area_description_ar' => null,
            'address' => $this->faker->streetAddress(),
            'client_address' => $this->faker->streetAddress(),
            'propery_type' => 1,
            'price' => 100,
            'title_image' => 'test.jpg',
            'three_d_image' => '',
            'video_link' => '',
            'latitude' => 0,
            'longitude' => 0,
            'status' => 1,
            'added_by' => $owner->id,
            'property_classification' => 4,
            'availability_type' => 1,
            'available_dates' => [],
            'slug_id' => 'test-property-' . $this->faker->unique()->slug(3),
        ];
    }
}

