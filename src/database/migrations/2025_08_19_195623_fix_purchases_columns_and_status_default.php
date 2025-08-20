<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class FixPurchasesColumnsAndStatusDefault extends Migration
{
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            if (!Schema::hasColumn('purchases', 'address_id')) {
                $table->foreignId('address_id')->nullable()->constrained();
            }
            if (!Schema::hasColumn('purchases', 'payment_intent_id')) {
                $table->string('payment_intent_id')->nullable();
                $table->index('payment_intent_id');
            }
            if (!Schema::hasColumn('purchases', 'stripe_session_id')) {
                $table->string('stripe_session_id')->nullable();
                $table->index('stripe_session_id');
            }
            if (!Schema::hasColumn('purchases', 'reserved_until')) {
                $table->timestamp('reserved_until')->nullable();
            }
            if (!Schema::hasColumn('purchases', 'paid_at')) {
                $table->timestamp('paid_at')->nullable();
            }
            if (!Schema::hasColumn('purchases', 'konbini_info')) {
                $table->json('konbini_info')->nullable();
            }
        });

        // 既存データの補正
        DB::table('purchases')
            ->where('status', 'pending')
            ->update(['status' => 'pending_payment']);

        // デフォルト値変更（dbal 必須）
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('status')->default('pending_payment')->change();
        });
    }

    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            // 先に index を安全に削除
            if (Schema::hasColumn('purchases', 'payment_intent_id')) {
                $table->dropIndex('purchases_payment_intent_id_index');
            }
            if (Schema::hasColumn('purchases', 'stripe_session_id')) {
                $table->dropIndex('purchases_stripe_session_id_index');
            }

            // 外部キー → カラムの順で削除（address_id）
            if (Schema::hasColumn('purchases', 'address_id')) {
                $table->dropConstrainedForeignId('address_id'); // FKも同時に削除
            }

            // その他のカラム削除（存在チェック）
            $drops = [];
            foreach (['payment_intent_id','stripe_session_id','reserved_until','paid_at','konbini_info'] as $col) {
                if (Schema::hasColumn('purchases', $col)) {
                    $drops[] = $col;
                }
            }
            if ($drops) {
                $table->dropColumn($drops);
            }

            // デフォルトを戻す（dbal 必須）
            $table->string('status')->default('pending')->change();
        });
    }
}