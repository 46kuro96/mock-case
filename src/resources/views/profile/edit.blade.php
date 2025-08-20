@extends('layouts.app')
<title>プロフィール編集</title>


@section('css')
<link rel="stylesheet" href="{{ asset('css/profile/edit.css') }}">
@endsection

@section('content')
<div class="container">
    <h2 class="profile-title">プロフィール設定</h2>
    <div class="profile-info">
        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" >
            @csrf
            @method($formMethod ?? 'PUT')
            <div class="form-group">
                <div class="image-select-container">
                    <div class="profile-image">
                        <img id="preview-image"
                            src="{{ (isset($user) && $user->profile_image) ? Storage::url($user->profile_image) : asset('image/no-image.png') }}"
                            alt="プロフィール画像"
                            style="display: {{ isset($user) && $user->profile_image ? 'block' : 'none' }};">
                    </div>
                    <div class="select-button-wrapper">
                        <label for="profile_image" class="select-button">
                            画像を選択する
                        </label>
                        <input type="file"
                            id="profile_image"
                            name="profile_image"
                            accept="image/jpeg,image/png,image/jpg,image/gif"
                            style="display: none;">
                    </div>
                </div>
                @error('profile_image')
                {{ $message }}
                @enderror
            </div>

            {{-- 入力要素の後に配置（または @push('scripts') でフッターへ） --}}
            <script>
            (function () {
            // DOM がまだなら待つ
            if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', attachPreview);
            } else {
            attachPreview();
            }

            function attachPreview() {
            var input = document.getElementById('profile_image');
            var img   = document.getElementById('preview-image');
            if (!input || !img) return;

            input.addEventListener('change', function (e) {
            var f = e.target.files && e.target.files[0];
            if (!f) return;
            // 画像以外は無視（念のため）
            if (!/^image\//.test(f.type)) return;
            img.src = URL.createObjectURL(f);
            img.style.display = 'block';
            });
            }
            })();
            </script>

            <div class="form-group">
                <label for="name" class="form-label">ユーザー名</label>
                <input class="form-input" type="text" name="name" id="name" value="{{ old('name', $user->name) }}">
                <div class="form-error">
                    @error('name')
                    {{ $message }}
                    @enderror
                </div>
            </div>
            <div class="form-group">
                <label for="post_code" class="form-label">郵便番号</label>
                <input class="form-input" type="text" name="post_code" id="post_code" pattern="\d{3}-\d{4}" value="{{ old('post_code', optional($user->addresses->first())->postal_code) }}" >
                <div class="form-error">
                    @error('post_code')
                    {{ $message }}
                    @enderror
                </div>
            </div>
            <div class="form-group">
                <label for="address" class="form-label">住所</label>
                <input class="form-input" type="text" name="address" id="address" value="{{ old('address', optional($user->addresses->first())->address) }}" >
                <div class="form-error">
                    @error('address')
                    {{ $message }}
                    @enderror
                </div>
            </div>
            <div class="form-group">
                <label for="building_name" class="form-label">建物名</label>
                <input class="form-input" type="text" name="building_name" id="building_name" value="{{ old('building_name', optional($user->addresses->first())->building_name) }}" >
            </div>
            <button type="submit" class="profile__button">更新する</button>
        </form>
    </div>
</div>
@endsection