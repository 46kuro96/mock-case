<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Address;
use Stripe\Stripe;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    // 購入画面表示
    public function create($item_id)
    {
        $item = Item::findOrFail($item_id);
        $availableStock = $item->stock - ($item->reserved_stock ?? 0);

        if ($availableStock < 1) {
            return redirect()->route('items.show', $item_id)
                             ->withErrors(['在庫がありません。']);
        }

        $address = auth()->user()->defaultAddress ?? auth()->user()->addresses()->first();

        return view('orders.purchase', compact('item', 'address'));
    }

    // 購入処理（カード / コンビニ分岐）
    public function store(Request $request, $item_id)
    {
        $request->validate([
            'payment_method' => 'required|in:credit_card,convenience_store',
            'address_id' => 'required|exists:addresses,id'
        ]);

        // 住所が自分のものか検証
        $address = Address::where('id', $request->address_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $item = Item::findOrFail($item_id);
        $availableStock = $item->stock - ($item->reserved_stock ?? 0);
        if ($availableStock < 1) {
            return back()->withErrors(['在庫がありません。']);
        }

        if ($request->payment_method === 'credit_card') {
            return $this->createStripeCheckout($request, $item);
        } else {
            return $this->createKonbiniPayment($request, $item, $address);
        }
    }

    // Stripe Checkout セッション作成（カード）
    private function createStripeCheckout(Request $request, Item $item)
    {
        try {
            $session = CheckoutSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'jpy',
                        'product_data' => ['name' => $item->title],
                        'unit_amount' => (int) $item->price, // JPY: ×100しない
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('purchase.success', ['item_id' => $item->id]) . '?session_id={CHECKOUT_SESSION_ID}&address_id=' . $request->address_id,
                'cancel_url'  => route('purchase.cancel', ['item_id' => $item->id]),
                'customer_email' => auth()->user()->email ?? null,
                'locale' => 'ja',
                'metadata' => [
                    'item_id' => $item->id,
                    'user_id' => auth()->id(),
                    'address_id' => $request->address_id,
                    'payment_method' => 'credit_card',
                ],
            ]);

            return redirect($session->url);

        } catch (\Throwable $e) {
            Log::error('Stripe checkout error: ' . $e->getMessage(), [
                'item_id' => $item->id,
                'user_id' => auth()->id()
            ]);
            return back()->withErrors(['決済処理でエラーが発生しました。']);
        }
    }

    // コンビニ払い作成（※Stripe呼び出しはトランザクション外）
    private function createKonbiniPayment(Request $request, Item $item, Address $address)
    {
        try {
            // 1) まず purchase + reserved_stock をDBトランザクションで確定
            $purchase = DB::transaction(function () use ($request, $item, $address) {
                // 在庫再チェック + ロック
                $lockedItem = Item::lockForUpdate()->findOrFail($item->id);
                $available = $lockedItem->stock - ($lockedItem->reserved_stock ?? 0);
                if ($available < 1) {
                    throw new \RuntimeException('在庫がありません。');
                }

                // 予約在庫 +1
                $lockedItem->increment('reserved_stock', 1);

                // pending レコードを先に作る（後続エラーでも監査のため残す）
                return Purchase::create([
                    'user_id'         => auth()->id(),
                    'item_id'         => $lockedItem->id,
                    'address_id'      => $address->id,
                    'payment_method'  => 'convenience_store',
                    'price_amount'    => $lockedItem->price,
                    'status'          => 'pending_payment',
                    'reserved_until'  => now()->addDays(7),
                ]);
            });

            // 2) Stripe PaymentIntent を作成（DBロック外）
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) $item->price, // 円
                'currency' => 'jpy',
                'payment_method_types' => ['konbini'],
                'receipt_email' => auth()->user()->email ?? null, // バウチャーメール
                'payment_method_options' => [
                    'konbini' => [
                        'expires_after_days' => 3,
                    ],
                ],
                'metadata' => [
                    'item_id'    => $item->id,
                    'user_id'    => auth()->id(),
                    'address_id' => $address->id,
                    'purchase_id'=> $purchase->id,
                ],
            ]);

            // 3) 作成したPIを保存
            $purchase->update(['payment_intent_id' => $paymentIntent->id]);

            // 4) confirm（⇒ next_action に伝票情報）
            $paymentIntent->confirm();

            return redirect()->route('purchase.konbini.instructions', [
                'item_id'    => $item->id,
                'purchase_id'=> $purchase->id,
            ]);

        } catch (\Throwable $e) {
            Log::error('Konbini payment creation error: ' . $e->getMessage(), [
                'item_id' => $item->id,
                'user_id' => auth()->id()
            ]);

            // 予約在庫をロールバック（purchase が作れていたら戻す）
            if (isset($purchase) && $purchase instanceof Purchase) {
                DB::transaction(function () use ($purchase) {
                    $item = Item::lockForUpdate()->find($purchase->item_id);
                    if ($item && ($item->reserved_stock ?? 0) > 0) {
                        $item->decrement('reserved_stock', 1);
                    }
                    $purchase->update(['status' => 'failed']);
                });
            }

            return back()->withErrors(['コンビニ支払いの作成でエラーが発生しました。']);
        }
    }

    // コンビニ手順ページ
    public function konbiniInstructions($item_id, $purchase_id)
    {
        $item = Item::findOrFail($item_id);
        $purchase = Purchase::where('id', $purchase_id)
                            ->where('user_id', auth()->id())
                            ->firstOrFail();

        if ($purchase->payment_method !== 'convenience_store') {
            return redirect()->route('items.show', $item_id);
        }

        try {
            $paymentIntent = PaymentIntent::retrieve($purchase->payment_intent_id);
            $konbiniInfo = null;

            if ($paymentIntent->next_action && $paymentIntent->next_action->type === 'konbini_display_details') {
                $konbiniInfo = $paymentIntent->next_action->konbini_display_details;
                // JSON に統一
                $purchase->update(['konbini_info' => $konbiniInfo]);
            }

            return view('purchase.konbini_instructions', compact('item', 'purchase', 'konbiniInfo'));

        } catch (\Throwable $e) {
            Log::error('Konbini instructions error: ' . $e->getMessage(), [
                'purchase_id' => $purchase->id,
                'payment_intent_id' => $purchase->payment_intent_id
            ]);
            return redirect()->route('items.show', $item_id)
                             ->withErrors(['支払い情報の取得に失敗しました。']);
        }
    }

    // 成功ページ（カード決済）
    public function success(Request $request, $item_id)
    {
        $item = Item::findOrFail($item_id);
        $session_id = $request->get('session_id');

        if ($session_id) {
            $this->processStripeSuccess($request, $item_id);
        }

        return view('orders.stripe_success', compact('item'));
    }

    private function processStripeSuccess(Request $request, $item_id)
    {
        try {
            $session = CheckoutSession::retrieve($request->get('session_id'));

            if ($session->payment_status === 'paid') {
                DB::transaction(function () use ($session, $item_id, $request) {
                    $existing = Purchase::where('stripe_session_id', $session->id)->first();
                    if ($existing) return;

                    $item = Item::lockForUpdate()->findOrFail($item_id);

                    Purchase::create([
                        'user_id'           => auth()->id(),
                        'item_id'           => $item_id,
                        'address_id'        => $request->get('address_id'),
                        'payment_method'    => 'credit_card',
                        'price_amount'      => $item->price,
                        'status'            => 'completed',
                        'stripe_session_id' => $session->id,
                        'paid_at'           => now(),
                    ]);

                    $item->decrement('stock', 1);
                });
            }

        } catch (\Throwable $e) {
            Log::error('Stripe success processing error: ' . $e->getMessage(), [
                'item_id' => $item_id,
                'session_id' => $request->get('session_id')
            ]);
        }
    }

    public function cancel($item_id)
    {
        $item = Item::findOrFail($item_id);
        return view('purchase.cancel', compact('item'));
    }
}