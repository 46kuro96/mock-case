@extends('layouts.app')
<title>会員登録</title>

@section('css')
<link rel="stylesheet" href="{{ asset('css/auth/register.css') }}">
@endsection

@section('content')
<div class="register">
    <h2 class="register__title">会員登録</h2>
    <form action="{{ route('register.store') }}" method="post" class="register-form" novalidate>
        @csrf
        <div class="form-group">
            <label for="name" class="form-label">ユーザー名</label>
            <input class="form-input" type="text" name="name" id="name" value="{{ old('name') }}" >
            <div class="form-error">
                @error('name')
                {{ $message }}
                @enderror
            </div>
        </div>
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
                    @if ($message !== 'パスワードと一致しません')
                    {{ $message }}
                    @endif
                @enderror
            </div>
        </div>
        <div class="form-group">
            <label for="password_confirmation" class="form-label">確認用パスワード</label>
            <input class="form-input" type="password" name="password_confirmation" id="password_confirmation" >
            <div class="form-error">
                @error('password')
                    @if($message === 'パスワードと一致しません')
                    {{ $message }}
                    @endif
                @enderror
                @error('password_confirmation')
                    {{ $message }}
                @enderror
            </div>
        </div>
        <button type="submit" class="register__button">登録する</button>
    </form>
    <div class="login-link">
        <a href="{{ route('login') }}">ログインはこちら</a>
    </div>
</div>
@endsection