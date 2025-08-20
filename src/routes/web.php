<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WebhookController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 商品一覧・詳細ページ
Route::get('/', [ItemController::class, 'index'])->name('items.index');
Route::get('/items/{item}', [ItemController::class, 'show'])->name('items.show');

// 認証関連
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login.form');
Route::post('/login', [LoginController::class, 'login'])->name('login');
Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Webhook
Route::post('/webhook/stripe', [WebhookController::class, 'handle'])
    ->withoutMiddleware(['VerifyCsrfToken::class'])
    ->name('webhook.stripe');

// 認証済みユーザー用ルート
Route::middleware('auth')->group(function () {
    // 出品
    Route::get('/sell', [ItemController::class, 'create'])->name('items.create');
    Route::post('/sell', [ItemController::class, 'store'])->name('items.store');

    // コメント
    Route::post('/comments/{item}', [CommentController::class, 'store'])->name('comments.store');

    // お気に入り
    Route::post('/favorites/toggle/{item_id}', [FavoriteController::class, 'toggle'])->name('favorites.toggle');
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');

    // プロフィール
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/setup', [ProfileController::class, 'setup'])->name('setup');
        Route::post('/setup', [ProfileController::class, 'store'])->name('store');
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('edit');
        Route::put('/update', [ProfileController::class, 'update'])->name('update');
    });

    // 住所管理
    Route::prefix('address')->name('address.')->group(function () {
        Route::get('/edit/{item_id}', [AddressController::class, 'edit'])->name('edit');
        Route::put('/update/{item_id}', [AddressController::class, 'update'])->name('update');
    });

    // 購入関連
    Route::prefix('purchase')->name('purchase.')->group(function () {
        // 購入画面表示
        Route::get('/{item_id}', [PurchaseController::class, 'create'])->name('create');

        // 購入処理
        Route::post('/{item_id}', [PurchaseController::class, 'store'])->name('store');

        // コンビニ決済指示画面
        Route::get('/{item_id}/konbini-instructions/{purchase_id}', [PurchaseController::class, 'konbiniInstructions'])
            ->name('konbini.instructions');

        // 購入成功画面
        Route::get('/{item_id}/success', [PurchaseController::class, 'success'])
            ->name('success');

        // 購入キャンセル画面
        Route::get('/{item_id}/cancel', [PurchaseController::class, 'cancel'])
            ->name('cancel');
    });
});