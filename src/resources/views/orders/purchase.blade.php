@extends('layouts.app')
<title>商品購入画面</title>

@section('css')
<link rel="stylesheet" href="{{ asset('css/orders/purchase.css') }}">
@endsection

@section('content')
<div class="container">
    <!-- 左側 -->
    <div class="main-content">
        <div class="product-info">
            <div class="product-image">
                @if($item->image)
                <img src="{{ asset($item->image) }}" alt="{{ $item->title }}" class="product-image__img">
                @else
                <img src="{{ asset('image/no-image.png') }}" alt="画像なし" class="product-image__img">
                @endif
            </div>
            <div class="product-details">
                <h2 class="product-title">{{ $item->title }}</h2>
                <p class="product-price">¥ {{ number_format($item->price) }}</p>
            </div>
        </div>

        <form id="purchase-form" method="POST" class="purchase-form" action="{{ route('purchase.store', ['item_id' => $item->id]) }}">
            @csrf
            <input type="hidden" name="address_id" value="{{ $address ? $address->id : '' }}">

            <div class="payment-method">
                <label for="payment" class="form-payment">支払い方法</label>
                <select name="payment_method" id="payment" class="form-select">
                    <option value="">選択してください</option>
                    <option value="convenience_store">コンビニ払い</option>
                    <option value="credit_card">カード支払い</option>
                </select>
            </div>
            @error('payment_method')
                {{ $message }}
            @enderror

            <div class="shipping-address">
                <div class="address-header">
                    <label class="form-label">配送先</label>
                    <a href="{{ route('address.edit', ['item_id' => $item->id]) }}" class="change-address">変更する</a>
                </div>
                <div class="address-info">
                    @if($address)
                        <p class="post-code">〒 {{ $address->postal_code }}</p>
                        <p class="address">{!! nl2br(e($address->address)) !!}
                            @if($address->building_name)
                                <br>{{ e($address->building_name) }}
                            @endif
                        </p>
                    @else
                        <p class="post-code">住所情報が登録されていません</p>
                        <p class="address">「変更する」から登録してください</p>
                    @endif
                </div>
                @error('address_id')
                    {{ $message }}
                @enderror
            </div>
        </form>
    </div>

    <!-- 右側 -->
    <div class="sidebar">
        <div class="order-summary">
            <div class="summary-item">
                <span class="summary-label">商品代金</span>
                <span class="summary-value">¥ {{ number_format($item->price) }}</span>
            </div>
            <div class="summary-item">
                <span class="summary-label">支払い方法</span>
                <span class="summary-value" id="selected-payment">選択してください</span>
            </div>
        </div>
        <button class="purchase-button" type="button" id="purchase-btn" {{ !$address ? 'disabled' : '' }}>
            購入する
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const paymentSelect = document.getElementById('payment');
    const paymentDisplay = document.getElementById('selected-payment');
    const purchaseForm = document.getElementById('purchase-form');
    const purchaseBtn = document.getElementById('purchase-btn');
    const addressId = @json($address ? $address->id : null);

    // 支払い方法表示更新
    function updatePaymentDisplay() {
        const selectedOption = paymentSelect.options[paymentSelect.selectedIndex];
        paymentDisplay.textContent = paymentSelect.value ? selectedOption.text : '選択してください';
    }
    paymentSelect.addEventListener('change', updatePaymentDisplay);
    updatePaymentDisplay();

    // ボタンクリック
    purchaseBtn.addEventListener('click', function() {
        const selectedPaymentMethod = paymentSelect.value;

        if (!selectedPaymentMethod) {
            alert('支払い方法を選択してください');
            return;
        }
        if (!addressId) {
            alert('配送先住所を設定してください');
            return;
        }

        purchaseBtn.disabled = true;
        purchaseBtn.textContent = '処理中...';

        // フォーム送信（サーバーサイドで支払い方法により処理を分岐）
        purchaseForm.submit();
    });
});
</script>
@endsection