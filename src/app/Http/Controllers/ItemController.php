<?php

namespace App\Http\Controllers;

use Illuminate\Pagination\LengthAwarePaginator;
use App\Http\Requests\ExhibitionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Item;
use App\Models\Category;
use App\Models\Favorite;

class ItemController extends Controller
{
    public function index(Request $request)
{
    $page = $request->query('page');
    $isMylist = ($page === 'mylist');

    if ($isMylist) {
        if (!auth()->check()) {
            // 未ログインなら空コレクションを返す
            $items = new LengthAwarePaginator([], 0, 12, $request->query('page', 1));
        } else {

        // マイリスト検索対応
        $items = auth()->user()->favoriteItems()
                    ->with('categories')
                    ->when($request->filled('keyword'), function($query) use ($request) {
                        $keyword = $request->input('keyword');
                        $query->where(function($q) use ($keyword) {
                            $q->where('title', 'like', "%{$keyword}%")
                              ->orWhere('description', 'like', "%{$keyword}%");
                        });
                    })
                    ->paginate(12)
                    ->appends($request->only('keyword', 'page'));
        }

        } else {
        // おすすめアイテム検索対応
        $items = Item::with('categories')
                ->latest()
                ->when(auth()->check(), function($query) {
                    $query->where('user_id', '!=', auth()->id());
                })
                ->when($request->filled('keyword'), function($query) use ($request) {
                    $keyword = $request->input('keyword');
                        $query->where(function($q) use ($keyword) {
                            $q->where('title', 'like', "%{$keyword}%")
                              ->orWhere('description', 'like', "%{$keyword}%");
                        });
                    })
                    ->paginate(12)
                    ->appends($request->only('keyword', 'page'));
        }

        return view('items.index', [
            'items' => $items,
            'isMylist' => $isMylist,
            'keyword' => $request->input('keyword', ''),
        ]);
    }

    public function show($item_id)
    {
        $item = Item::with('categories', 'comments.user', 'favorites')
                    ->withCount(['comments', 'favorites'])
                    ->findOrFail($item_id);

        return view('items.show', compact('item'));
    }

    public function create()
    {
        $categories = Category::all();  // カテゴリ一覧を取得
        $item = new Item();             // 空のItemインスタンス
        return view('items.create', compact('categories', 'item'));
    }

    public function store(ExhibitionRequest $request)
    {
        $validated = $request->validated();

        // 画像保存
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('items', 'public');
        }

        $categories = $validated['categories'];
        unset($validated['categories']);

        $validated['user_id'] = auth()->id();
        $validated['status'] = 'available';

        $item = Item::create($validated);
        $item->categories()->attach($categories);

        return redirect()->route('items.index')->with('success', '商品を出品しました。');
    }
}