<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            ['name' => 'ファッション', 'slug' => 'fashion'],
            ['name' => '家電', 'slug' => 'electronics'],
            ['name' => 'インテリア', 'slug' => 'interior'],
            ['name' => 'レディース', 'slug' => 'ladies'],
            ['name' => 'メンズ', 'slug' => 'mens'],
            ['name' => 'コスメ', 'slug' => 'cosmetics'],
            ['name' => '本', 'slug' => 'books'],
            ['name' => 'ゲーム', 'slug' => 'games'],
            ['name' => 'スポーツ', 'slug' => 'sports'],
            ['name' => 'キッチン', 'slug' => 'kitchen'],
            ['name' => 'ハンドメイド', 'slug' => 'handmade'],
            ['name' => 'アクセサリー', 'slug' => 'accessories'],
            ['name' => 'おもちゃ', 'slug' => 'toys'],
            ['name' => 'ベビー・キッズ', 'slug' => 'baby_kids'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
