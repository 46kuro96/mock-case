<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        if (app()->environment('local')) {

            // 外部キー無効化
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // 存在するテーブルだけtruncate
            $tables = ['favorites', 'category_item', 'items', 'categories', 'users'];
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                }
            }

            // 外部キー有効化
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        // シーダーを呼び出す
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            ItemSeeder::class
        ]);
    }
}
