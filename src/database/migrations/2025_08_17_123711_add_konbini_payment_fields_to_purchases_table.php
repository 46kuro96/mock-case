<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // 追加

class AddKonbiniPaymentFieldsToPurchasesTable extends Migration
{
    public function up()
    {
        Schema::table('purchases', function (Blueprint $table) {
            // 住所FK
            if (!Schema::hasColumn('purchases', 'address_id')) {
                $table->foreignId('address_id')
                      ->nullable()
                      ->constrained()
                      ->after('user_id');
            }

            // コンビニ: PaymentIntent ID
            if (!Schema::hasColumn('purchases', 'payment_intent_id')) {
                $table->string('payment_intent_id')->nullable()->after('stripe_payment_id');
                $table->index('payment_intent_id');
            }

            // カード: Checkout Session ID（別マイグレーションと重複しうるため hasColumn ガードあり）
            if (!Schema::hasColumn('purchases', 'stripe_session_id')) {
                $table->string('stripe_session_id')->nullable()->after('payment_intent_id');
                $table->index('stripe_session_id');
            }

            // コンビニの取り置き期限
            if (!Schema::hasColumn('purchases', 'reserved_until')) {
                $table->timestamp('reserved_until')->nullable()->after('purchased_at');
            }

            // バウチャ情報は JSON に統一
            if (!Schema::hasColumn('purchases', 'konbini_info')) {
                $table->json('konbini_info')->nullable()->after('reserved_until');
            }

            // 入金完了日時
            if (!Schema::hasColumn('purchases', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('purchased_at');
            }
        });

        // 既存データのステータス補正
        DB::table('purchases')
            ->where('status', 'pending')
            ->update(['status' => 'pending_payment']);

        // ※ 任意：デフォルト値の変更（dbal 必須）
        // Schema::table('purchases', function (Blueprint $table) {
        //     $table->string('status')->default('pending_payment')->change();
        // });
    }

    public function down()
    {
        Schema::table('purchases', function (Blueprint $table) {
            // 先にインデックス/外部キーを安全に削除
            if (Schema::hasColumn('purchases', 'payment_intent_id')) {
                $table->dropIndex('purchases_payment_intent_id_index');
            }
            if (Schema::hasColumn('purchases', 'stripe_session_id')) {
                $table->dropIndex('purchases_stripe_session_id_index');
            }
            if (Schema::hasColumn('purchases', 'address_id')) {
                // 外部キーを先に落とす
                $table->dropConstrainedForeignId('address_id');
            }

            // カラム削除（存在チェック付き）
            $dropCols = [];
            foreach (['payment_intent_id','stripe_session_id','reserved_until','konbini_info','paid_at'] as $col) {
                if (Schema::hasColumn('purchases', $col)) $dropCols[] = $col;
            }
            if (!empty($dropCols)) {
                $table->dropColumn($dropCols);
            }

            // ※ 任意：ステータス default を戻すなら（dbal 必須）
            // $table->string('status')->default('pending')->change();
        });
    }
}