<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWebhookColumnsToPurchasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('purchases', function (Blueprint $table) {
            // Webhookで必要な追加カラム
            $table->string('stripe_session_id')->nullable()
                ->after('stripe_payment_id')
                ->comment('StripeチェックアウトセッションID');

            $table->boolean('webhook_processed')->default(false)
                ->after('stripe_session_id')
                ->comment('Webhook処理完了フラグ');

            // インデックス追加（検索性能向上）
            $table->index('stripe_session_id');
            $table->index(['webhook_processed', 'created_at']);
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('purchases', function (Blueprint $table) {
             // インデックス削除
            $table->dropIndex(['purchases_stripe_session_id_index']);
            $table->dropIndex(['purchases_webhook_processed_created_at_index']);
            $table->dropIndex(['purchases_transaction_id_index']);

            // カラム削除
            $table->dropColumn([
                'stripe_session_id',
                'webhook_processed'
            ]);
        });
    }
}
