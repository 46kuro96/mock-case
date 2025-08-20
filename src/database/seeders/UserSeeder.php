<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $faker = Faker::create(); // Faker インスタンスを作成

        User::where('email', 'test@example.com')->delete();
        // 既存のユーザーを削除してから再作成

        // ログイン確認用
        $user = User::factory()->create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        // 住所を紐付け
        $user->addresses()->create([
            'postal_code'   => '123-4567',
            'address'       => '東京都世田谷区1-1-1',
            'building_name' => 'テストマンション101',
            'is_default'    => true,
        ]);

        // ダミーユーザーを10人作成
        User::factory()->count(10)->create()->each(function ($user) use ($faker) {
            $user->addresses()->create([
                'postal_code' => $faker->postcode(), // 郵便番号
                'address'     => $faker->address(), // 住所
                'building_name' => $faker->secondaryAddress(), // 建物名
                'is_default'  => true, // デフォルト住所
            ]);
        });
    }
}