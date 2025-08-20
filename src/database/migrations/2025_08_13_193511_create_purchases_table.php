<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('price_amount');
            $table->string('status')->default('pending'); // 購入ステータス
            $table->string('stripe_payment_id')->nullable(); // Stripeの支払いIDを保存するカラム
            $table->timestamp('purchased_at')->nullable(); // 購入日時を保存するカラム
            $table->string('address')->nullable(); // 購入時の住所を保存するカラム
            $table->string('postal_code')->nullable(); // 郵便番号を保存するカラム
            $table->string('payment_method')->nullable(); // 支払い方法を保存するカラム（例: credit_card, bank_transfer）
            $table->string('transaction_id')->nullable(); // 取引IDを保存するカラム
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchases');
    }
}
