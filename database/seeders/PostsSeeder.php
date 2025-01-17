<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Posts;
use Faker\Factory as Faker;
use App\Models\User;

class PostsSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        $faker = Faker::create();

        // Get all user ids
        $userIds = User::pluck('id')->toArray();

        // Define the array containing the list of posts
        $postTypes = [
            "Accounting", "Agriculture", "Construction", "Education", "Finance", "Retail",
            "Fashion Photography Studios", "Healthcare", "Coffee Shops", "Information Technology",
            "Shopping Malls", "Trading Goods", "Consulting", "Barbershop", "Fashion Consultancy",
            "Beauty Salon", "Logistics", "Sports", "Pets", "Entertainment", "Pattern Making Services",
            "Maintenance", "Pharmaceuticals", "Automotive", "Environmental", "Food & Beverage",
            "Garment Manufacturing", "Fashion Events Management", "Retail Clothing Stores",
            "Fashion Design Studios", "Shoe Manufacturing", "Tailoring and Alterations",
            "Textile Printing and Embroidery", "Fashion Accessories", "Boutiques",
            "Apparel Recycling and Upcycling", "Apparel Exporters"
        ];

        foreach ($postTypes as $type) {
            for ($i = 0; $i < 20; $i++) {
                Posts::factory()->create([
                    'businessName' => $faker->unique()->company,
                    'description' => $faker->sentence(),
                    'images' => json_encode($this->getRandomImage()),
                    'type' => $type, // Assign the current type from the $postTypes array
                    'is_active' => 1,
                    'contactNumber' => $faker->unique()->randomNumber(9, true), // Generate a random 9-digit contact number
                    'user_id' => $faker->randomElement($userIds), // Assign a random user_id
                ]);
            }
        }
    }

    /**
     * Get a random image URL from a free image hosting service.
     *
     * @return array
     */
    private function getRandomImage()
    {
        $imageHosts = [
            'https://source.unsplash.com/collection/928423/480x480', // Unsplash
            // Add more free image hosting URLs here if needed
        ];

        $images = [];
        for ($i = 0; $i < 3; $i++) {
            // Choose a random image URL from the array of hosts
            $randomHost = array_rand($imageHosts);
            $images[] = $imageHosts[$randomHost];
        }

        return $images;
    }
}
