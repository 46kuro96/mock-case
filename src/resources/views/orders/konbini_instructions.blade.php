@extends('layouts.app')
<title>コンビニ支払いのご案内</title>

@section('css')
<link rel="stylesheet" href="{{ asset('css/orders/konbini.css') }}">
@endsection

@section('content')
<div class="container">
  <h2 class="mb-3">コンビニ支払いのご案内</h2>
  <p class="text-muted">以下の番号を使って、期限までにお支払いください。</p>

@php
  // $konbiniInfo は配列 or オブジェクトを想定
  $info = is_array($konbiniInfo) ? $konbiniInfo : (array) $konbiniInfo;

  // Stripe の構造: konbini_display_details.stores.{lawson,ministop,familymart,seicomart}
  $stores = $info['stores'] ?? ($info['store'] ?? null);
  if (is_object($stores)) $stores = (array) $stores;

  $expires = $info['expires_at'] ?? null; // epoch 秒 or string
  $url     = $info['instructions_url'] ?? null;
  $url     = is_string($url) ? $url : null;

  $labels = [
    'lawson'     => 'ローソン',
    'ministop'   => 'ミニストップ',
    'familymart' => 'ファミリーマート',
    'seicomart'  => 'セイコーマート',
  ];
@endphp

@if(empty($info))
  <div class="alert alert-warning">支払い情報の取得に失敗しました。時間をおいて再度お試しください。</div>
@else
  <div class="card">

    {{-- 店舗ごとの番号 --}}
    @if(!empty($stores) && is_array($stores))
      <div class="mb-2"><strong>店舗別のお支払い番号</strong></div>
      <ul style="margin-left:1em;">
        @foreach($stores as $storeName => $s)
          @php
            $arr = is_object($s) ? (array)$s : (array)$s;
            $paymentCode = $arr['payment_code'] ?? $arr['reference_number'] ?? null;
            $confirmCode = $arr['confirmation_number'] ?? null;
            $label = $labels[$storeName] ?? ucfirst($storeName);
          @endphp
          <li style="margin-bottom:.5rem;">
            <div><strong>{{ $label }}</strong></div>
            @if($paymentCode)<div>お客様番号：<code>{{ $paymentCode }}</code></div>@endif
            @if($confirmCode)<div>確認番号  ：<code>{{ $confirmCode }}</code></div>@endif
          </li>
        @endforeach
      </ul>
    @else
      {{-- 単一の番号だけ来る場合 --}}
      @if(!empty($info['reference_number']))
        <div class="mb-2"><strong>お客様番号</strong>：<code>{{ $info['reference_number'] }}</code></div>
      @endif
      @if(!empty($info['confirmation_number']))
        <div class="mb-2"><strong>確認番号</strong>：<code>{{ $info['confirmation_number'] }}</code></div>
      @endif
    @endif

    {{-- 支払い期限 --}}
    @if($expires)
      <div class="mb-2">
        <strong>支払い期限</strong>：
        {{ is_numeric($expires)
            ? \Carbon\Carbon::createFromTimestamp((int)$expires)->format('Y/m/d H:i')
            : (string)$expires }}
      </div>
    @endif

    {{-- Stripe の公式手順ページ --}}
    @if($url)
      <div class="mb-2">
        <a href="{{ $url }}" target="_blank" rel="noopener">支払い手順の詳細（Stripe）</a>
      </div>
    @endif

  </div>
@endif

  <div class="mt-4">
    <a href="{{ route('items.index') }}" class="btn btn-secondary">トップページに戻る</a>
  </div>
</div>
@endsection