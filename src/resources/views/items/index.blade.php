@extends('layouts.app')
<title>商品一覧画面</title>

@section('css')
<link rel="stylesheet" href="{{ asset('css/items/index.css') }}">
@endsection
@section('content')
<div class="container">
    <div class="tab-menu">
    <a href="{{ route('items.index') }}"
        class="{{ !request()->has('page') ? 'active' : '' }}">
        おすすめ
    </a>

    <a href="{{ route('items.index', ['page' => 'mylist']) }}"
        class="{{ request()->get('page') === 'mylist' ? 'active' : '' }}">
        マイリスト
    </a>
</div>
    <!-- 商品グリッド -->
    <div class="item-grid">
        @forelse($items as $item)
            <div class="item-card">
                <a href="{{ route('items.show', $item->id) }}" class="item-link">
                    <img src="{{ asset($item->image) }}" alt="{{ $item->title }}" class="item-image">
                    <h2>{{ $item->title }}</h2>
                </a>
                @if($item->is_sold)
                    <span class="sold-badge">Sold</span>
                @endif
            </div>
        @empty
            <p>まだマイリストに商品がありません</p>
        @endforelse
    </div>
    <!-- ページネーション -->
    <div class="pagination">
        {{ $items->appends(request()->except('page'))->links() }}
    </div>
</div>
@endsection