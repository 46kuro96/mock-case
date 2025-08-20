@extends('layouts.app')
<title>購入キャンセル</title>

@section('css')
<link rel="stylesheet" href="{{ asset('css/orders/cancel.css') }}">
@endsection

@section('content')
<div class="container">
    <h2>決済がキャンセルされました ❌</h2>
    <p>商品: {{ $item->title }}</p>
    <p>キャンセルされた場合、購入は完了していません。</p>
    <a href="{{ url('/') }}">トップページへ戻る</a>
</div>
@endsection