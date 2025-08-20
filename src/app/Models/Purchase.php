<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Purchase extends Model
{
    /**
     * 一括代入可能な属性
     */
    protected $fillable = [
        'item_id',
        'user_id',
        'address_id',
        'price_amount',
        'status',
        'payment_method',
        'stripe_payment_id',
        'stripe_session_id',
        'payment_intent_id',
        'webhook_processed',
        'purchased_at',
        'paid_at',
        'address',
        'postal_code',
        'transaction_id',
        'reserved_until',
        'konbini_info',
        'customer_name',
        'customer_email',
    ];

    /**
     * 属性キャスト
     */
    protected $casts = [
        'webhook_processed' => 'boolean',
        'price_amount' => 'integer',
        'purchased_at' => 'datetime',
        'paid_at' => 'datetime',
        'reserved_until' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'konbini_info' => 'array',
    ];

    /**
     * リレーション: 購入者
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * リレーション: 購入商品
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * リレーション: 配送先住所
     */
    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    /**
     * スコープ: Webhook処理済みの購入
     */
    public function scopeWebhookProcessed($query)
    {
        return $query->where('webhook_processed', true);
    }

    /**
     * スコープ: 完了した購入
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * スコープ: 支払い待ち
     */
    public function scopePendingPayment($query)
    {
        return $query->where('status', 'pending_payment');
    }

    /**
     * スコープ: コンビニ払い
     */
    public function scopeConvenienceStore($query)
    {
        return $query->where('payment_method', 'convenience_store');
    }

    /**
     * スコープ: クレジットカード払い
     */
    public function scopeCreditCard($query)
    {
        return $query->where('payment_method', 'credit_card');
    }

    /**
     * スコープ: 特定のStripeセッション
     */
    public function scopeByStripeSession($query, $sessionId)
    {
        return $query->where('stripe_session_id', $sessionId);
    }

    /**
     * スコープ: 特定のPaymentIntent
     */
    public function scopeByPaymentIntent($query, $paymentIntentId)
    {
        return $query->where('payment_intent_id', $paymentIntentId);
    }

    /**
     * スコープ: 予約期限切れ
     */
    public function scopeExpiredReservation($query)
    {
        return $query->where('reserved_until', '<', now())
                     ->where('status', 'pending_payment');
    }

    /**
     * アクセサ: 金額を日本円形式で取得
     */
    public function getFormattedPriceAttribute(): string
    {
        return '¥' . number_format($this->price_amount);
    }

    /**
     * アクセサ: ステータスの日本語表示
     */
    public function getStatusLabelAttribute(): string
    {
        switch ($this->status) {
            case 'pending':
                return '処理中';
            case 'pending_payment':
                return '支払い待ち';
            case 'completed':
                return '完了';
            case 'failed':
                return '失敗';
            case 'canceled':
                return 'キャンセル';
            case 'shipped':
                return '発送済み';
            case 'delivered':
                return '配達完了';
            default:
                return '不明';
    }
    }

    /**
     * アクセサ: 支払い方法の日本語表示
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        switch ($this->payment_method) {
            case 'credit_card':
                return 'クレジットカード';
            case 'convenience_store':
                return 'コンビニ払い';
            case 'bank_transfer':
                return '銀行振込';
            case 'paypal':
                return 'PayPal';
            default:
                return 'その他';
        }
    }

    /**
     * アクセサ: コンビニ情報を取得
     */
    public function getKonbiniDetailsAttribute()
    {
        if ($this->konbini_info && is_array($this->konbini_info)) {
            return (object) $this->konbini_info;
        }
        
        if ($this->konbini_info && is_string($this->konbini_info)) {
            return json_decode($this->konbini_info);
        }
        
        return null;
    }

    /**
     * アクセサ: 配送先住所の整形
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->postal_code ? '〒' . $this->postal_code : null,
            $this->address
        ]);
        
        return implode(' ', $parts);
    }

    /**
     * アクセサ: 予約が有効かどうか
     */
    public function getIsReservationValidAttribute(): bool
    {
        if (!$this->reserved_until) {
            return true;
        }
        
        return $this->reserved_until > now();
    }

    /**
     * アクセサ: 支払い期限までの残り時間（分）
     */
    public function getMinutesUntilExpirationAttribute(): int
    {
        if (!$this->reserved_until) {
            return 0;
        }
        
        return max(0, now()->diffInMinutes($this->reserved_until, false));
    }

    /**
     * メソッド: 購入完了処理
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'purchased_at' => now(),
            'paid_at' => now(),
            'webhook_processed' => true,
        ]);
    }

    /**
     * メソッド: 支払い失敗処理
     */
    public function markAsFailed(): void
    {
        $this->update([
            'status' => 'failed',
            'webhook_processed' => true,
        ]);
    }

    /**
     * メソッド: キャンセル処理
     */
    public function markAsCanceled(): void
    {
        $this->update([
            'status' => 'canceled',
            'webhook_processed' => true,
        ]);
    }

    /**
     * メソッド: 予約期限の延長
     */
    public function extendReservation(int $days = 7): void
    {
        $this->update([
            'reserved_until' => now()->addDays($days)
        ]);
    }

    /**
     * メソッド: コンビニ支払い情報の更新
     */
    public function updateKonbiniInfo(array $konbiniInfo): void
    {
        $this->update([
            'konbini_info' => json_encode($konbiniInfo)
        ]);
    }

    /**
     * メソッド: 配送先住所情報の更新
     */
    public function updateShippingAddress(string $address, string $postalCode = null): void
    {
        $updates = ['address' => $address];
        
        if ($postalCode) {
            $updates['postal_code'] = $postalCode;
        }
        
        $this->update($updates);
    }

    /**
     * メソッド: 注文番号の生成
     */
    public function getOrderNumberAttribute(): string
    {
        return 'ORD-' . str_pad($this->id, 8, '0', STR_PAD_LEFT);
    }

    /**
     * メソッド: 支払い方法がコンビニかどうか
     */
    public function isConvenienceStorePayment(): bool
    {
        return $this->payment_method === 'convenience_store';
    }

    /**
     * メソッド: 支払い方法がクレジットカードかどうか
     */
    public function isCreditCardPayment(): bool
    {
        return $this->payment_method === 'credit_card';
    }

    /**
     * メソッド: 購入が完了しているかどうか
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * メソッド: 支払い待ち状態かどうか
     */
    public function isPendingPayment(): bool
    {
        return $this->status === 'pending_payment';
    }

    /**
     * メソッド: 期限切れかどうか
     */
    public function isExpired(): bool
    {
        if (!$this->reserved_until) {
            return false;
        }

        return $this->reserved_until < now() && $this->isPendingPayment();
    }
}