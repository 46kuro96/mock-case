<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Purchase;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\PurchaseRequest;
use Stripe\Stripe;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\PaymentIntent;

class PurchaseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    // 購入画面
    public function create($item_id)
    {
        $item = Item::findOrFail($item_id);
        $available = $item->stock - ($item->reserved_stock ?? 0);
        if ($available < 1) {
            return redirect()->route('items.show', $item_id)->withErrors(['在庫がありません。']);
        }

        $address = auth()->user()->defaultAddress ?? auth()->user()->addresses()->first();
        return view('orders.purchase', compact('item', 'address'));
    }

    // 購入処理（カード/コンビニ 振り分け）
    public function store(PurchaseRequest $request, $item_id)
    {
         $data = $request->validated();
        // 自分の住所かを検証
        $address = Address::where('id', $request->input('address_id'))
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $item = Item::findOrFail($item_id);
        $available = $item->stock - ($item->reserved_stock ?? 0);
        if ($available < 1) {
            return back()->withErrors(['在庫がありません。'])->withInput();
        }

        if ($request->payment_method === 'credit_card') {
            return $this->createStripeCheckout($request, $item);
        }
        return $this->processConvenienceStorePurchase($request, $item, $address);
    }

    // カード（Checkout）
    private function createStripeCheckout(Request $request, Item $item)
    {
        try {
            $session = CheckoutSession::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency'     => 'jpy',
                        'product_data' => ['name' => $item->title],
                        'unit_amount'  => (int) $item->price, // JPY はゼロ小数
                    ],
                    'quantity' => 1,
                ]],
                'mode'           => 'payment',
                'success_url'    => route('purchase.success', ['item_id' => $item->id])
                                    . '?session_id={CHECKOUT_SESSION_ID}&address_id=' . $request->input('address_id'),
                'cancel_url'     => route('purchase.cancel', ['item_id' => $item->id]),
                'customer_email' => auth()->user()->email ?? null,
                'locale'         => 'ja',
                'metadata'       => [
                    'item_id'        => $item->id,
                    'user_id'        => auth()->id(),
                    'address_id'     => $request->input('address_id'),
                    'payment_method' => 'credit_card',
                ],
            ]);

            return redirect($session->url);
        } catch (\Throwable $e) {
            Log::error('Stripe checkout error: ' . $e->getMessage(), [
                'item_id' => $item->id,
                'user_id' => auth()->id(),
            ]);
            return back()->withErrors(['決済処理でエラーが発生しました。']);
        }
    }

    // コンビニ（PaymentIntent）
    private function processConvenienceStorePurchase(Request $request, Item $item, Address $address)
    {
        try {
            // 1) 在庫予約 + pending をDBトランザクションで確定（外部APIはここで呼ばない）
            $purchase = DB::transaction(function () use ($item, $address) {
                $locked = Item::lockForUpdate()->findOrFail($item->id);
                $available = $locked->stock - ($locked->reserved_stock ?? 0);
                if ($available < 1) {
                    throw new \RuntimeException('在庫がありません。');
                }
                // 予約在庫 +1
                $locked->increment('reserved_stock', 1);

                return Purchase::create([
                    'user_id'        => auth()->id(),
                    'item_id'        => $locked->id,
                    'address_id'     => $address->id,
                    'payment_method' => 'convenience_store',
                    'price_amount'   => $locked->price,
                    'status'         => 'pending_payment',
                    'reserved_until' => now()->addDays(7),
                    // 表示用に連結（アプリ内では $address->address / building_name を使う）
                    'address'        => trim(($address->address ?? '') . ' ' . ($address->building_name ?? '')),
                    'postal_code'    => $address->postal_code,
                ]);
            });

            // 2) Stripe PI 作成 + confirm（ここはDBロック外）
            $user = auth()->user();
            $paymentIntent = PaymentIntent::create([
                'amount'               => (int) $item->price,
                'currency'             => 'jpy',
                'payment_method_types' => ['konbini'],
                'receipt_email'        => $user->email ?? null,
                'payment_method_options' => [
                    'konbini' => ['expires_after_days' => 3], // 支払期限（Stripe側）
                ],
                // ← これを指定しないと「payment method が無い」と怒られる
                'payment_method_data' => [
                    'type' => 'konbini',
                    'billing_details' => [
                        'name'  => $user->name ?? 'ゲスト',
                        'email' => $user->email ?? null,
                    ],
                ],
                'confirm'  => true,
                'metadata' => [
                    'item_id'     => $item->id,
                    'user_id'     => $user->id,
                    'address_id'  => $address->id,
                    'purchase_id' => $purchase->id,
                ],
            ]);

            // 3) PI ID と手順情報を保存
            $konbiniInfo = null;
            if ($paymentIntent->next_action && $paymentIntent->next_action->type === 'konbini_display_details') {
                $konbiniInfo = $paymentIntent->next_action->konbini_display_details;
            }
            $purchase->update([
                'payment_intent_id' => $paymentIntent->id,
                'konbini_info'      => $konbiniInfo,  // Purchase::$casts で 'array' にしておく
            ]);

            // 4) 手順ページへ（ビューは resources/views/orders/konbini_instructions.blade.php）
            return redirect()->route('purchase.konbini.instructions', [
                'item_id'     => $item->id,
                'purchase_id' => $purchase->id,
            ]);

        } catch (\Throwable $e) {
            Log::error('Konbini payment creation error: ' . $e->getMessage(), [
                'item_id' => $item->id,
                'user_id' => auth()->id(),
            ]);

            // pending 作成済みなら予約在庫を戻す
            if (isset($purchase) && $purchase instanceof Purchase) {
                DB::transaction(function () use ($purchase) {
                    $locked = Item::lockForUpdate()->find($purchase->item_id);
                    if ($locked && ($locked->reserved_stock ?? 0) > 0) {
                        $locked->decrement('reserved_stock', 1);
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

        // 保存済みを優先
        $konbiniInfo = $purchase->konbini_info;

        // 無ければ Stripe から再取得して保存
        if (empty($konbiniInfo)) {
            try {
                $pi = PaymentIntent::retrieve($purchase->payment_intent_id);
                if ($pi->next_action && $pi->next_action->type === 'konbini_display_details') {
                    $konbiniInfo = $pi->next_action->konbini_display_details;
                    $purchase->update(['konbini_info' => $konbiniInfo]);
                }
            } catch (\Throwable $e) {
                Log::error('Konbini instructions error: ' . $e->getMessage(), [
                    'purchase_id' => $purchase->id,
                    'payment_intent_id' => $purchase->payment_intent_id,
                ]);
                // 失敗してもビューは表示（Blade 側で「取得失敗」を出す）
            }
        }

        return view('orders.konbini_instructions', compact('item', 'purchase', 'konbiniInfo'));
    }

    // カード：成功ページ
    public function success(Request $request, $item_id)
    {
        $item = Item::findOrFail($item_id);
        if ($request->has('session_id')) {
            $this->processStripeSuccess($request, $item_id);
        }
        return view('orders.stripe_success', compact('item'));
    }

    // カード：ブラウザ帰還時の補完（本番は Webhook が正）
    private function processStripeSuccess(Request $request, $item_id)
    {
        try {
            $session = CheckoutSession::retrieve($request->get('session_id'));
            if ($session->payment_status === 'paid') {
                DB::transaction(function () use ($session, $item_id, $request) {
                    $exists = Purchase::where('stripe_session_id', $session->id)->first();
                    if ($exists) return;

                    $item = Item::lockForUpdate()->findOrFail($item_id);
                    if ($item->stock < 1) return;

                    $address = Address::find($request->get('address_id'));

                    Purchase::create([
                        'user_id'           => auth()->id(),
                        'item_id'           => $item_id,
                        'address_id'        => $request->get('address_id'),
                        'payment_method'    => 'credit_card',
                        'price_amount'      => $item->price,
                        'status'            => 'completed',
                        'stripe_payment_id' => $session->id,
                        'stripe_session_id' => $session->id,
                        'address'           => $address ? trim(($address->address ?? '') . ' ' . ($address->building_name ?? '')) : null,
                        'postal_code'       => $address->postal_code ?? null,
                        'purchased_at'      => now(),
                        'paid_at'           => now(),
                    ]);

                    $item->decrement('stock', 1);
                });
            }
        } catch (\Throwable $e) {
            Log::error('Stripe success processing error: ' . $e->getMessage(), [
                'item_id' => $item_id,
                'session_id' => $request->get('session_id'),
            ]);
        }
    }

    // キャンセルページ
    public function cancel($item_id)
    {
        $item = Item::findOrFail($item_id);
        return view('orders.stripe_cancel', compact('item'));
    }
}