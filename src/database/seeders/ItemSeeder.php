<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $items = [
            [
                'title' => '腕時計',
                'price' => 15000,
                'description' => 'スタイリッシュなデザインのメンズ腕時計',
                'image' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Armani+Mens+Clock.jpg',
                'condition' => '良好',
                'status' => '販売中',
            ],
            [
                'title' => 'HDD',
                'price' => 5000,
                'description' => '高速で信頼性の高いハードディスク',
                'image' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/HDD+Hard+Disk.jpg',
                'condition' => '目立った傷や汚れなし',
                'status' => '販売中',
            ],
            [
                'title' => '玉ねぎ3束',
                'price' => 300,
                'description' => '新鮮な玉ねぎ3束のセット',
                'image' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/iLoveIMG+d.jpg',
                'condition' => 'やや傷や汚れあり',
                'status' => '売却済み',
            ],
            [
                'title' => '革靴',
                'price' => 4000,
                'description' => 'クラシックなデザインの革靴',
                'image' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Leather+Shoes+Product+Photo.jpg',
                'condition' => '状態が悪い',
                'status' => '販売中',
            ],
            [
                'title' => 'ノートPC',
                'price' => 45000,
                'description' => '高性能なノートパソコン',
                'image' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Living+Room+Laptop.jpg',
                'condition' => '良好',
                'status' => '売却済み',
            ],
            [
                'title' => 'マイク',
                'price' => 8000,
                'description' => '高音質のレコーディング用マイク',
                'image' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Music+Mic+4632231.jpg',
                'condition' => '目立った傷や汚れなし',
                'status' => '販売中',
            ],
            [
                'title' => 'ショルダーバッグ',
                'price' => 3500,
                'description' => 'おしゃれなショルダーバッグ',
                'image' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Purse+fashion+pocket.jpg',
                'condition' => 'やや傷や汚れあり',
                'status' => '販売中',
            ],
            [
                'title' => 'タンブラー',
                'price' => 500,
                'description' => '使いやすいタンブラー',
                'image' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Tumbler+souvenir.jpg',
                'condition' => '状態が悪い',
                'status' => '売却済み',
            ],
            [
                'title' => 'コーヒーミル',
                'price' => 4000,
                'description' => '手動のコーヒーミル',
                'image' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/Waitress+with+Coffee+Grinder.jpg',
                'condition' => '良好',
                'status' => '売却済み',
            ],
            [
                'title' => 'メイクセット',
                'price' => 2500,
                'description' => '便利なメイクアップセット',
                'image' => 'https://coachtech-matter.s3.ap-northeast-1.amazonaws.com/image/%E5%A4%96%E5%87%BA%E3%83%A1%E3%82%A4%E3%82%AF%E3%82%A2%E3%83%83%E3%83%95%E3%82%9A%E3%82%BB%E3%83%83%E3%83%88.jpg',
                'condition' => '目立った傷や汚れなし',
                'status' => '売却済み',
            ],
        ];

        $users = User::where('email', '!=', 'test@example.com')->pluck('id')->toArray(); // テストユーザーを除外
        $categories = Category::pluck('id')->toArray(); // 全カテゴリID取得

        foreach ($items as $item) {
            // 出品者をダミー１０人からランダムに選ぶ
            $item['user_id'] = Arr::random($users);

            try {
            // 画像をダウンロード
            $response = Http::get($item['image']);
             // 画像のバイナリデータ
            $imageContents = $response->body();
            // 元ファイル名を取得（URLの最後の部分）
            $originalFilename = basename(parse_url($item['image'], PHP_URL_PATH));
            // 万が一ファイル名が被るのを避けるため、プレフィックスをつけても良い
            $filename = uniqid('item_') . '_' . $originalFilename;
            // 保存先（storage/app/public/items）
            Storage::disk('public')->put("items/{$filename}", $imageContents);
            // DBには public/storage/items/xxx.jpg のように保存
            $item['image'] = "storage/items/{$filename}";
        } catch (\Exception $e) {
            $item['image'] = null; // 画像取得失敗時はnullに設定
        }

            $newItem = Item::create($item);

            // ランダムに1~3つのカテゴリを紐付け
            $randomCategories = Arr::random($categories, rand(1, 3));
            $newItem->categories()->attach((array) $randomCategories);
        }
    }
}