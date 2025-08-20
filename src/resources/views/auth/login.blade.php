@extends('layouts.app')
<title>ログイン</title>

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/login.css') }}">
@endsection

@section('content')
<div class="login">
    <h2 class="login__title">ログイン</h2>
    <form action="{{ route('login') }}" method="post" class="login-form" novalidate>
        @csrf
        @if ($errors->has('login'))
            <div class="form-error">
                {{ $errors->first('login') }}
            </div>
        @endif

        <div class="form-group">
            <label for="email" class="form-label">メールアドレス</label>
            <input class="form-input" type="email" name="email" id="email" value="{{ old('email') }}" >
            <div class="form-error">
                @error('email')
                {{ $message }}
                @enderror
            </div>
        </div>
        <div class="form-group">
            <label for="password" class="form-label">パスワード</label>
            <input class="form-input" type="password" name="password" id="password" >
            <div class="form-error">
                @error('password')
                {{ $message }}
                @enderror
            </div>
        </div>
        <button type="submit" class="login__button">ログインする</button>
        </form>
    <div class="register-link">
        <a href="{{ route('register') }}">会員登録はこちら</a>
    </div>
</div>
@endsection