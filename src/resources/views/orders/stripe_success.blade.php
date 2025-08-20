@extends('layouts.app')
<title>購入完了</title>

@section('css')
<link rel="stylesheet" href="{{ asset('css/orders/success.css') }}">
@endsection


@section('content')
<div class="container">
    <div class="success-message">
        <h2>購入が完了しました！</h2>
        <p>{{ $item->title }}をご購入いただき、ありがとうございます。</p>
    </div>

    <div class="purchased-item">
        <img src="{{ asset($item->image) }}" alt="{{ $item->title }}">
        <div class="item-info">
            <h3>{{ $item->title }}</h3>
            <p class="price">¥{{ number_format($item->price) }}(税込)</p>
        </div>
    </div>

    <a href="{{ route('items.index') }}" class="return-button">
        トップページに戻る
    </a>
</div>
@endsection