<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Purchase;
use App\Models\Item;
use App\Models\User;
use App\Models\Address;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;
use Exception;

class WebhookController extends Controller
{
    /**
     * Stripe Webhookイベントを処理
     */
    public function handle(Request $request): Response
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $endpoint_secret = config('services.stripe.webhook_secret');

        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (UnexpectedValueException $e) {
            Log::error('Stripe Webhook: Invalid payload', ['error' => $e->getMessage()]);
            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            Log::error('Stripe Webhook: Invalid signature', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        try {
            switch ($event['type']) {
                // カード（Checkout）
                case 'checkout.session.completed':
                    $this->handleCheckoutCompleted($event['data']['object']);
                    break;

                // コンビニ（PaymentIntent）
                case 'payment_intent.succeeded':
                    $this->handlePaymentSucceeded($event['data']['object']);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentFailed($event['data']['object']);
                    break;

                case 'payment_intent.canceled':
                    $this->handlePaymentCanceled($event['data']['object']);
                    break;

                // （任意）next_action のバウチャ情報を保存したい場合
                case 'payment_intent.requires_action':
                    $this->handlePaymentRequiresAction($event['data']['object']);
                    break;

                default:
                    Log::info('Stripe Webhook: Unknown event type', ['event_type' => $event['type']]);
            }
        } catch (Exception $e) {
            Log::error('Stripe Webhook: Error processing event', [
                'event_type' => $event['type'],
                'error' => $e->getMessage()
            ]);
            return response('Internal server error', 500);
        }

        return response('Webhook handled', 200);
    }

    /**
     * Checkout Session 完了時（カード決済）
     */
    private function handleCheckoutCompleted(array $session): void
    {
        Log::info('Stripe Webhook: Checkout session completed', [
            'session_id' => $session['id'],
            'payment_status' => $session['payment_status']
        ]);

        if (($session['payment_status'] ?? null) !== 'paid') return;

        $item_id       = $session['metadata']['item_id']    ?? null;
        $user_id       = $session['metadata']['user_id']    ?? null;
        $address_id    = $session['metadata']['address_id'] ?? null;
        $paymentMethod = $session['metadata']['payment_method'] ?? 'credit_card';

        if (!$item_id || !$user_id) {
            Log::error('Stripe Webhook: Missing metadata', ['metadata' => $session['metadata'] ?? []]);
            return;
        }

        DB::transaction(function () use ($session, $item_id, $user_id, $address_id, $paymentMethod) {
            // 二重作成防止
            $existing = Purchase::where('stripe_session_id', $session['id'])->first();
            if ($existing) return;

            $item    = Item::lockForUpdate()->find($item_id);
            $user    = User::find($user_id);
            $address = $address_id ? Address::find($address_id) : null;

            if (!$item || !$user) return;
            if ($item->stock < 1) return;

            // JPYはゼロ小数
            $amount = (int) ($session['amount_total'] ?? 0);

            $purchase = Purchase::create([
                'item_id'           => $item_id,
                'user_id'           => $user_id,
                'address_id'        => $address_id,
                'price_amount'      => $amount,
                'status'            => 'completed',
                'payment_method'    => $paymentMethod,
                'stripe_payment_id' => $session['id'],   // 互換のため残すなら
                'stripe_session_id' => $session['id'],
                'webhook_processed' => true,
                'purchased_at'      => now(),
                'paid_at'           => now(),
                // 任意：配送情報（Checkoutの住所がある場合のみ）
                'address'           => $address ? trim(($address->address_line_1 ?? '').' '.($address->address_line_2 ?? '')) : null,
                'postal_code'       => $address->postal_code ?? null,
            ]);

            $item->decrement('stock', 1);
            $this->updateItemSoldStatus($item);
            $this->updateShippingAddress($purchase, $session);

            Log::info('Stripe Webhook: Purchase completed', [
                'purchase_id' => $purchase->id,
                'item_id'     => $item_id
            ]);
        });
    }

    /**
     * コンビニ：入金成功
     */
    private function handlePaymentSucceeded(array $payment_intent): void
    {
        Log::info('Stripe Webhook: Payment succeeded', ['payment_intent_id' => $payment_intent['id'] ?? null]);

        DB::transaction(function () use ($payment_intent) {
            $piId = $payment_intent['id'] ?? null;
            if (!$piId) return;

            $purchase = Purchase::where('payment_intent_id', $piId)->first();
            if (!$purchase) return;
            if ($purchase->status === 'completed') return;

            $purchase->update([
                'status'            => 'completed',
                'paid_at'           => now(),
                'webhook_processed' => true,
            ]);

            $item = $purchase->item()->lockForUpdate()->first();
            if ($item) {
                if (Schema::hasColumn('items', 'reserved_stock') && ($item->reserved_stock ?? 0) > 0) {
                    $item->decrement('reserved_stock', 1);
                }
                $item->decrement('stock', 1);
                $this->updateItemSoldStatus($item);
            }
        });
    }

    /**
     * コンビニ：支払い失敗（期限切れなど）
     */
    private function handlePaymentFailed(array $payment_intent): void
    {
        Log::error('Stripe Webhook: Payment failed', ['payment_intent_id' => $payment_intent['id'] ?? null]);

        DB::transaction(function () use ($payment_intent) {
            $piId = $payment_intent['id'] ?? null;
            if (!$piId) return;

            $purchase = Purchase::where('payment_intent_id', $piId)->first();
            if (!$purchase) return;

            $purchase->update([
                'status'            => 'failed',
                'webhook_processed' => true,
            ]);

            if ($purchase->payment_method === 'convenience_store') {
                $this->releaseReservedStock($purchase);
            } else {
                $this->restoreItemStock($purchase);
            }
        });
    }

    /**
     * コンビニ：キャンセル
     */
    private function handlePaymentCanceled(array $payment_intent): void
    {
        Log::info('Stripe Webhook: Payment canceled', ['payment_intent_id' => $payment_intent['id'] ?? null]);

        DB::transaction(function () use ($payment_intent) {
            $piId = $payment_intent['id'] ?? null;
            if (!$piId) return;

            $purchase = Purchase::where('payment_intent_id', $piId)->first();
            if (!$purchase) return;

            $purchase->update([
                'status'            => 'canceled',
                'webhook_processed' => true,
            ]);

            if ($purchase->payment_method === 'convenience_store') {
                $this->releaseReservedStock($purchase);
            } else {
                $this->restoreItemStock($purchase);
            }
        });
    }

    /**
     * （任意）コンビニ：伝票情報の更新（next_action）
     */
    private function handlePaymentRequiresAction(array $payment_intent): void
    {
        $type = $payment_intent['next_action']['type'] ?? null;
        if ($type !== 'konbini_display_details') return;

        DB::transaction(function () use ($payment_intent) {
            $piId = $payment_intent['id'] ?? null;
            if (!$piId) return;

            $purchase = Purchase::where('payment_intent_id', $piId)->first();
            if (!$purchase) return;

            $purchase->update([
                'konbini_info' => $payment_intent['next_action']['konbini_display_details'] ?? null,
            ]);

            Log::info('Stripe Webhook: Konbini info updated (requires_action)', [
                'purchase_id' => $purchase->id
            ]);
        });
    }

    /**
     * 予約在庫解除（失敗/キャンセル時）
     */
    private function releaseReservedStock(Purchase $purchase): void
    {
        $item = $purchase->item;
        if ($item && ($item->reserved_stock ?? 0) > 0) {
            $item->decrement('reserved_stock', 1);
            $this->updateItemSoldStatus($item);
        }
    }

    /**
     * カード系の回復（必要に応じて）
     */
    private function restoreItemStock(Purchase $purchase): void
    {
        $item = $purchase->item;
        if ($item) {
            $item->increment('stock', 1);
            $this->updateItemSoldStatus($item);
        }
    }

    /**
     * 在庫ゼロ判定の同期（is_sold カラムがあれば）
     */
    private function updateItemSoldStatus(Item $item): void
    {
        if (!Schema::hasColumn('items', 'is_sold')) return;

        $available = (int) $item->stock - (int) ($item->reserved_stock ?? 0);
        $shouldBeSold = $available <= 0;

        if ((bool) $item->is_sold !== $shouldBeSold) {
            $item->update(['is_sold' => $shouldBeSold]);
        }
    }

    /**
     * Checkoutの顧客住所が取れた場合に補完
     */
    private function updateShippingAddress(Purchase $purchase, array $session): void
    {
        try {
            $stripe_session = \Stripe\Checkout\Session::retrieve(
                $session['id'],
                ['expand' => ['customer_details']]
            );

            $customer = $stripe_session->customer_details ?? null;
            if ($customer && isset($customer->address)) {
                $addr = $customer->address;
                $full_address = trim(implode(' ', array_filter([
                    $addr->line1 ?? null,
                    $addr->line2 ?? null,
                    $addr->city ?? null,
                    $addr->state ?? null,
                ])));

                $updates = [];
                if ($full_address && (empty($purchase->address))) {
                    $updates['address'] = $full_address;
                }
                if (!empty($addr->postal_code) && (empty($purchase->postal_code))) {
                    $updates['postal_code'] = $addr->postal_code;
                }
                if ($updates) $purchase->update($updates);
            }
        } catch (Exception $e) {
            Log::warning('Stripe Webhook: Failed to update address', [
                'purchase_id' => $purchase->id,
                'error' => $e->getMessage()
            ]);
        }
    }
}