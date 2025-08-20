@extends('layouts.app')
<title>プロフィール画面</title>

@section('css')
<link rel="stylesheet" href="{{ asset('css/profile/index.css') }}">
@endsection

@section('content')
<div class="container">
    <div class="profile-info">
        @if($user->profile_image)
            <img src="{{ $user->profile_image_url }}" alt="プロフィール画像" class="profile-avatar">
        @else
            <div class="profile-avatar no-image"></div>
        @endif

        <div class="profile-detail">
            <h2>{{ $user->name }}</h2>
        </div>
        <a href="{{ route('profile.edit') }}" class="edit-profile">プロフィールを編集</a>
    </div>

    <div class="tab-menu">
        <a href="{{ route('profile.index', ['page' => 'sell']) }}" class="{{ $page === 'sell' ? 'active' : '' }}">出品した商品</a>
        <a href="{{ route('profile.index', ['page' => 'buy']) }}" class="{{ $page === 'buy' ? 'active' : '' }}">購入した商品</a>
    </div>
    <div class="item-grid">
        @if($page === 'sell')
            @forelse($listings as $item)
                <div class="item-card">
                    <a href="{{ route('items.show', $item->id) }}" class="item-link">
                    <img src="{{ asset($item->image) }}" alt="{{ $item->title }}" class="item-image">
                    <h2>{{ $item->title }}</h2>
                </a>
                </div>
            @empty
                <p>出品した商品はありません。</p>
            @endforelse
        @elseif($page === 'buy')
            @forelse($purchases as $purchase)
            @php $item = $purchase->item; @endphp  {{-- $purchase から商品を取得 --}}
                <div class="item-card">
                    <a href="{{ route('items.show', $item->id) }}" class="item-link">
                    <img src="{{ asset($item->image) }}" alt="{{ $item->title }}" class="item-image">
                    <h2>{{ $item->title }}</h2>
                </a>
                </div>
            @empty
                <p>購入した商品はありません。</p>
            @endforelse
        @else
        @endif
    </div>
</div>
@endsection