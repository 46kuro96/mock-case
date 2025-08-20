@extends('layouts.app')
<title>商品詳細画面</title>

@section('css')
<link rel="stylesheet" href="{{ asset('css/items/show.css') }}">
@endsection

@section('content')
<div class="container">
    <div class="image-info">
        <div class="image-detail">
            <div class="item-image">
                @if ($item->image)
                <img src="{{ asset($item->image) }}" alt="{{ $item->title }}">
                @else
                <img src="{{ asset('image/no-image.png') }}" alt="No Image">
                @endif
                @if($item->is_sold)
                    <span class="sold-badge">Sold</span>
                @endif
            </div>
        </div>
    </div>

    <div class="item-detail">
        <div class="item-info">
            <h1 class="item-title">{{ $item->title }}</h1>
            <p class="item-brand">{{ $item->brand }}</p>
            <p class="item-price">¥{{ number_format($item->price) }}(税込)</p>

            <div class="item-action">
                <form action="{{ route('favorites.toggle', ['item_id' => $item->id]) }}" method="post">
                    @csrf
                    <button type="submit" class="favorite-button {{ auth()->user() && auth()->user()->favorites()->where('item_id', $item->id)->exists() ? 'active' : '' }}">
                        @if (auth()->user() && auth()->user()->favorites()->where('item_id', $item->id)->exists())
                        <img src="{{ asset('image/okiniiri.png') }}" alt="お気に入りアイコン">
                        {{ $item->favorites_count }} (お気に入り済み)
                        @else
                        <img src="{{ asset('image/okiniiri.png') }}" alt="お気に入りアイコン">
                        {{ $item->favorites_count }}
                        @endif
                    </button>
                </form>
                <a href="#comments" class="comment-button">
                    <img src="{{ asset('image/comment.png') }}" alt="コメントアイコン">
                    {{ $item->comments_count }}
                </a>
            </div>
            <a href="{{ route('purchase.create', ['item_id' => $item->id]) }}" class="purchase-button">
            購入手続きへ</a>

            <div class="item-description">
                <h2>商品説明</h2>
                <p>{{ $item->description }}</p>
            </div>
            <div class="item-information">
                <h2>商品の情報</h2>
                @if ($item->categories)
                <p>カテゴリー {{ $item->categories->pluck('name')->join(', ') }}</p>
                @endif
                <p>商品の状態 {{ $item->condition_label }}</p>
            </div>

            <div class="item-comment">
                <h2>コメント</h2>
                @if ($item->comments->isEmpty())
                <p>コメントはまだありません。</p>
                @else
                <ul>
                @foreach ($item->comments as $comment)
                    <li>
                        <strong>{{ $comment->user->name }}:</strong> {{ $comment->content }}
                        <span class="comment-date">{{ $comment->created_at->format('Y年m月d日 H:i') }}</span>
                    </li>
                @endforeach
                </ul>
                @endif

                <form action="{{ route('comments.store', ['item' => $item->id]) }}" method="POST">
                @csrf
                <div class="form-comment">
                    <label for="comment">商品へのコメント</label>
                    <textarea name="content" id="comment" rows="4" ></textarea>
                    @error('content')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="comment-submit-button">コメントを送信する</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

