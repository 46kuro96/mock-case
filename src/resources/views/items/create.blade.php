@extends('layouts.app')
<title>商品出品画面</title>

@section('css')
<link rel="stylesheet" href="{{ asset('css/items/create.css') }}">
@endsection

@section('content')
<div class="container">
    <h1>商品出品</h1>
    <form action="{{ route('items.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="create-group">
            <h2 class="logo">商品画像</h2>
            <div class="image">
                <input type="file" name="image" id="image" multiple accept="image/*" class="image-input">
                <label for="image" class="image-button">画像を選択する</label>
                <div id="image-preview"></div>
            </div>
            @error('image')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="create-group">
            <h2 class="logo-detail">商品の詳細</h2>
        </div>

        <div class="create-group">
            <label>カテゴリー</label>
            <div class="category-grid">
                @php
                    $selectedCategories = old('categories', []);
                @endphp

                @foreach($categories as $category)
                    <div class="category-item">
                        <input type="checkbox" name="categories[]" value="{{ $category->id }}" id="category_{{ $category->id }}" class="category-checkbox" {{ in_array($category->id, $selectedCategories) ? 'checked' : '' }}>
                        <label for="category_{{ $category->id }}" class="category-label">{{ $category->name }}</label>
                    </div>
                    @endforeach
            </div>
            @error('categories')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="create-group">
            <label for="condition">商品の状態</label>
            <select name="condition" id="condition">
                <option value="">選択してください</option>
                <option value="like_new" {{ old('condition') == 'like_new' ? 'selected' : '' }}>良好</option>
                <option value="good" {{ old('condition') == 'good' ? 'selected' : '' }}>目立った傷や汚れなし</option>
                <option value="fair" {{ old('condition') == 'fair' ? 'selected' : '' }}>やや傷や汚れあり</option>
                <option value="damaged" {{ old('condition') == 'damaged' ? 'selected' : '' }}>状態が悪い</option>
            </select>
            @error('condition')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="create-group">
            <h2 class="logo-detail">商品名と説明</h2>
        </div>

        <div class="create-group">
            <label for="title">商品名</label>
            <input class="input-title" type="text" name="title" id="title" value="{{ old('title') }}" >
            @error('title')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>
        <div class="create-group">
            <label for="brand">ブランド名</label>
            <input class="input-brand" type="text" name="brand" id="brand" value="{{ old('brand') }}">
        </div>
        <div class="create-group">
            <label for="description">商品説明</label>
            <textarea class="textarea" name="description" id="description" rows="6" >{{ old('description') }}</textarea>
            @error('description')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>
        <div class="create-group">
            <label for="price">販売価格</label>
            <div class="price-input-with-yen">
                <span class="yen">￥</span>
                <input class="input-price" type="number" name="price" id="price" value="{{ old('price') }}" min="0">
            </div>
            @error('price')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>
        <div class="create-group">
            <button type="submit" class="submit-button">出品する</button>
        </div>
    </form>
</div>


<!-- 画像の読み込み・プレビュー表示・ボタンで削除・フロント側で動作 -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded');

    const fileInput = document.querySelector('#image');
    const imagePreview = document.querySelector('#image-preview');
    console.log('fileInput:', fileInput);
    console.log('imagePreview:', imagePreview);

    if (!fileInput) {
        console.error('ファイル入力要素が見つかりません - ID: #image');
        return;
    }

    if (!imagePreview) {
        console.error('プレビュー要素が見つかりません - ID: #image-preview');
        return;
    }

    let selectedFiles = [];

    try {
        fileInput.addEventListener('change', function(e) {
            console.log('ファイルが選択されました:', e.target.files);

            if (e.target.files && e.target.files.length > 0) {
                selectedFiles = Array.from(e.target.files);
                renderPreviews();
            } else {
                console.log('No files selected');
                imagePreview.innerHTML = '';
            }
        });
    } catch (error) {
        console.error('addEventListener error:', error);
        return;
    }

    function renderPreviews() {
        console.log('renderPreviews called, selectedFiles:', selectedFiles);

        imagePreview.innerHTML = '';

        if (!selectedFiles || selectedFiles.length === 0) {
            console.log('No files to preview');
            return;
        }

        selectedFiles.forEach((file, index) => {
            console.log('Processing file:', file.name, file.type);

            if (!file.type.startsWith('image/')) {
                console.warn('Skipping non-image file:', file.name);
                return;
            }

            const reader = new FileReader();

            reader.onload = function(e) {
                console.log('File loaded:', file.name);

                try {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'image-preview-item';

                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = `プレビュー${index + 1}`;

                    const removeBtn = document.createElement('button');
                    removeBtn.type = 'button';
                    removeBtn.textContent = '×';
                    removeBtn.className = 'image-remove-button';
                    removeBtn.dataset.index = index;

                    wrapper.appendChild(img);
                    wrapper.appendChild(removeBtn);
                    imagePreview.appendChild(wrapper);

                    removeBtn.addEventListener('click', function() {
                        removeImage(parseInt(this.dataset.index));
                    });

                } catch (error) {
                    console.error('Error creating preview:', error);
                }
            };

            reader.onerror = function() {
                console.error('Error reading file:', file.name);
            };

            reader.readAsDataURL(file);
        });
    }

    function removeImage(index) {
        console.log('Removing image at index:', index);

        if (index >= 0 && index < selectedFiles.length) {
            selectedFiles.splice(index, 1);

            try {
                const dataTransfer = new DataTransfer();
                selectedFiles.forEach(file => dataTransfer.items.add(file));
                fileInput.files = dataTransfer.files;

                renderPreviews();
            } catch (error) {
                console.error('Error updating file input:', error);

                renderPreviews();
            }
        }
    }

    setTimeout(() => {
        console.log('Post-load check:');
        console.log('fileInput still exists:', !!document.querySelector('#image'));
        console.log('imagePreview still exists:', !!document.querySelector('#image-preview'));
    }, 1000);
});

window.addEventListener('load', function() {
    console.log('Window fully loaded');

    const fileInput = document.getElementById('image');
    const imagePreview = document.getElementById('image-preview');

    if (!fileInput || !imagePreview) {
        console.error('Elements missing after window load');
        console.log('Available elements with id="image":', document.querySelectorAll('#image'));
        console.log('Available elements with id="image-preview":', document.querySelectorAll('#image-preview'));
    }
});
</script>
@endsection