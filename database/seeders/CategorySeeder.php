<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Seed the default editorial categories.
     */
    public function run(): void
    {
        $categories = [
            'News',
            'Taal Volcano',
            'Weather',
            'Traffic',
            'Tourism',
            'Food & Drink',
            'Events',
            'Business',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]);
        }
    }
}
