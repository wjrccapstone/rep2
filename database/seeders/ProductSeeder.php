<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIds = Category::pluck('id', 'name');

        // Availability is derived from stock by the Product model, so it is not seeded.
        $products = [
            ['name' => 'Acer Aspire 5 A515 i5 12th Gen', 'category' => 'Laptops', 'price' => 35000, 'stock' => 8],
            ['name' => 'HP 14s-dq5 i3 12th Gen', 'category' => 'Laptops', 'price' => 28500, 'stock' => 0],
            ['name' => 'Lenovo IdeaPad 3 i5 12th Gen', 'category' => 'Laptops', 'price' => 32000, 'stock' => 4],
            ['name' => 'ASUS VivoBook 15 OLED i5', 'category' => 'Laptops', 'price' => 39990, 'stock' => 20],
            ['name' => 'Kingston 8GB DDR4 3200MHz RAM', 'category' => 'Components', 'price' => 1800, 'stock' => 0, 'on_order' => true],
            ['name' => 'Kingston 16GB DDR4 3200MHz RAM', 'category' => 'Components', 'price' => 3200, 'stock' => 0, 'on_order' => true],
            ['name' => 'WD Blue 1TB HDD', 'category' => 'Components', 'price' => 2600, 'stock' => 15],
            ['name' => 'Samsung 970 EVO 500GB NVMe SSD', 'category' => 'Components', 'price' => 3800, 'stock' => 6],
            ['name' => 'Logitech M185 Wireless Mouse', 'category' => 'Peripherals', 'price' => 650, 'stock' => 25],
            ['name' => 'Logitech K120 Keyboard', 'category' => 'Peripherals', 'price' => 550, 'stock' => 0],
            ['name' => 'SanDisk 64GB USB Flash Drive', 'category' => 'Storage', 'price' => 350, 'stock' => 30],
            ['name' => 'Seagate 1TB External HDD', 'category' => 'Storage', 'price' => 3200, 'stock' => 5],
            ['name' => 'Laptop Cooling Pad', 'category' => 'Accessories', 'price' => 750, 'stock' => 12],
            ['name' => 'HDMI Cable 1.5m', 'category' => 'Accessories', 'price' => 180, 'stock' => 40],
        ];

        foreach ($products as $product) {
            $categoryName = $product['category'];
            unset($product['category']);

            Product::create([
                ...$product,
                'category_id' => $categoryIds[$categoryName],
                'code' => Product::nextCode(),
                'status' => 'active',
            ]);
        }
    }
}
