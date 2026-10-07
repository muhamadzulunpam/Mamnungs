<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@mamnungs.test',
            'password' => 'password',
            'role' => 'admin',
        ]);

        User::create([
            'name' => 'Kasir',
            'email' => 'kasir@mamnungs.test',
            'password' => 'password',
            'role' => 'kasir',
        ]);

      
        $sourceImage = database_path('seeders/images/esteler.jpeg');
        $targetPath = 'products/esteler.jpeg';

        if (file_exists($sourceImage)) {
            Storage::disk('public')->put(
                $targetPath,
                file_get_contents($sourceImage)
            );
        }

        $imagePath = 'products/esteler.jpeg'; 

        $menu = [
            'Es Teler' => [
                ['Es Teler Original', 15000],
                ['Es Teler Durian',   22000],
                ['Es Teler Spesial',  20000],
            ],
            'Es Campur' => [
                ['Es Campur',  14000],
                ['Es Cendol',  12000],
            ],
            'Minuman' => [
                ['Es Teh Manis', 5000],
                ['Es Jeruk',     8000],
            ],
        ];

        foreach ($menu as $categoryName => $products) {
            $category = Category::create([
                'name' => $categoryName,
                'slug' => str($categoryName)->slug(),
            ]);

            foreach ($products as [$name, $price]) {
                Product::create([
                    'category_id' => $category->id,
                    'name'        => $name,
                    'price'       => $price,
                    'image'       => $imagePath, 
                ]);
            }
        }
    }
}