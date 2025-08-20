@extends('layouts.app')
<title>送付先住所変更画面</title>

@section('css')
<link rel="stylesheet" href="{{ asset('css/addresses/edit.css') }}">
@endsection

@section('css')
<link rel="stylesheet" href="{{ asset('css/addresses/edit.css') }}">
@endsection

@section('content')
<div class="container">
    <h2 class="address__title">住所の変更</h2>
    <form action="{{ route('address.update', ['item_id' => $item->id]) }}" method="POST" class="address-form">
        @csrf
        @method('PUT')
        <div class="form-group">
            <label for="post_code" class="form-label">郵便番号</label>
            <input class="form-input" type="text" name="post_code" id="post_code" pattern="\d{3}-\d{4}" value="{{ old('post_code', optional($address)->postal_code) }}" >
            <div class="form-error">
                @error('post_code') {{ $message }} @enderror
            </div>
        </div>
        <div class="form-group">
            <label for="address" class="form-label">住所</label>
            <input class="form-input" type="text" name="address" id="address" value="{{ old('address', optional($address)->address) }}" >
            <div class="form-error">
                @error('address') {{ $message }} @enderror
            </div>
        </div>
        <div class="form-group">
            <label for="building_name" class="form-label">建物名</label>
            <input class="form-input" type="text" name="building_name" id="building_name" value="{{ old('building_name', optional($address)->building_name) }}" >
        </div>
        <button type="submit" class="address__button">更新する</button>
    </form>
</div>
@endsection