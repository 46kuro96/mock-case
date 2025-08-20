<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="{{asset('css/sanitize.css')}}">
    <link rel="stylesheet" href="{{asset('css/common.css')}}">
    @yield('css')
</head>

<body>
    <header class="header">
        <div class="header__inner">
            <a href="{{ url('/') }}" class="header__logo">
                <img src="{{ asset('image/logo.svg') }}" alt="ロゴ">
            </a>

            @if (!Request::is('login') && !Request::is('register'))
            @auth
            <form action="{{ route('items.index') }}" method="get" class="header__search">
                @if ($isMylist ?? false)
                <input type="hidden" name="page" value="mylist">
                @endif
                <input type="text" name="keyword" value="{{ request('keyword') }}" placeholder="何をお探しですか？" class="header__search-input">
            </form>
            <div class="nav__list">
                <form action="/logout" method="post">
                    @csrf
                    <button type="submit" class="nav__list-log">ログアウト</button>
                </form>
                <a href="{{ route('profile.index') }}" class="nav__list-mypage">マイページ</a>
                <a href="{{ route('items.create') }}" class="nav__list-sell">出品</a>
            </div>
            @else
            <form action="/search" method="get" class="header__search">
                    <input type="text" name="keyword" placeholder="何をお探しですか？" class="header__search-input">
            </form>
            <div class="nav__list">
                <a href="{{ route('login.form') }}" class="nav__list-log">ログイン</a>
                <a href="{{ route('profile.index') }}" class="nav__list-mypage">マイページ</a>
                <a href="{{ route('items.create') }}" class="nav__list-sell">出品</a>
            </div>
            @endauth
            @endif
        </div>
    </header>

    <main>
        @yield('content')
    </main>
</body>
</html>