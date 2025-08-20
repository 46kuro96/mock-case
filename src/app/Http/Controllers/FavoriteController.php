<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Favorite;
use App\Models\Item;

class FavoriteController extends Controller
{
    public function index()
    {
        $favorites = Favorite::where('user_id', auth()->id())
                    ->with('item') // 関連づけられたitemを取得
                    ->get()
                    ->pluck('item'); // itemだけをコレクションとして取り出す

        return view('items.index', [
            'items' => $favorites,
            'isMylist' => true
        ]);
    }

    public function toggle(Request $request, $item_id)
    {
        $user = auth()->user();

        if ($user->favorites()->where('item_id', $item_id)->exists()) {
            // お気に入りを削除
            $user->favorites()->where('item_id', $item_id)->delete();
            $message = 'お気に入りを解除しました。';
        } else {
            // お気に入りに追加
            $user->favorites()->create(['item_id' => $item_id]);
            $message = 'お気に入りに追加しました。';
        }

        return redirect()->route('items.show', $item_id)->with('success', $message);
    }

    public function store($itemId)
    {
        $user = auth()->user();
        $user->favoriteItems()->attach($itemId);

        return back()->with('success', 'マイリストに追加しました');
    }
}
