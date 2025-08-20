<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Purchase;
use App\Models\Item;
use App\Http\Requests\ProfileRequest;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    // マイページ / プロフィール画面
    public function index(Request $request)
    {
        $user = Auth::user();
        // コレクション経由ではなくクエリで1件取得（無駄なロードを回避）
        $address = $user->addresses()->first();

        $page = $request->query('page'); // ?page=buy or sell
        $purchases = collect();
        $listings  = collect();

        if ($page === 'buy') {
            $purchases = Purchase::where('user_id', $user->id)
                                ->with('item')
                                ->latest()
                                ->get();
        } elseif ($page === 'sell') {
            $listings = Item::where('user_id', $user->id)
                            ->latest()
                            ->get();
        }

        return view('profile.index', compact('user', 'address', 'page', 'purchases', 'listings'));
    }

    // 初回登録フォーム
    public function setup()
    {
        $user = Auth::user();
        return view('profile.setup', compact('user'));
    }

    // 初回登録保存（ProfileRequestで検証）
    public function store(ProfileRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();

        // 画像があれば保存（DBには相対パスのみ保存）
        $imagePath = null;
        if ($request->hasFile('profile_image')) {
            $imagePath = $request->file('profile_image')->store('profile_images', 'public'); // ex) profile_images/xxx.jpg
        }

        // ユーザー更新
        $user->update([
            'name'          => $data['name'],
            'profile_image' => $imagePath, // null許容 OK
        ]);

        // 住所作成（is_default を true にして1件目を作る）
        $user->addresses()->create([
            'postal_code'   => $data['post_code'],
            'address'       => $data['address'],
            'building_name' => $data['building_name'] ?? null,
            'is_default'    => true,
        ]);

        return redirect()->route('profile.index')->with('success', 'プロフィールを登録しました');
    }

    // 編集フォーム
    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', [
            'user'       => $user,
            'formMethod' => 'PUT',
            'formAction' => route('profile.update'),
        ]);
    }

    // 編集保存（ProfileRequestで検証）
    public function update(ProfileRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();

        // 画像があれば保存（DBには相対パスのみ保存）
        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('profile_images', 'public');
            $user->profile_image = $path;
        }

        // ユーザー名更新
        $user->name = $data['name'];
        $user->save();

        // 住所 upsert（is_default=true を1件維持）
        $payload = [
            'postal_code'   => $data['post_code'],
            'address'       => $data['address'],
            'building_name' => $data['building_name'] ?? null,
            'is_default'    => true,
        ];
        $address = $user->addresses()->first();
        if ($address) {
            $address->update($payload);
        } else {
            $user->addresses()->create($payload);
        }

        return redirect()->route('profile.index')->with('success', 'プロフィールを更新しました');
    }
}