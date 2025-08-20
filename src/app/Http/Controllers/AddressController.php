<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AddressRequest;

class AddressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function edit($item_id)
    {
        $user = Auth::user();
        $address = $user->addresses()->first(); // 既存があれば取得（is_default優先にしてもOK）
        $item = Item::findOrFail($item_id);

        return view('address.edit', compact('address', 'item'));
    }

    public function update(AddressRequest $request, $item_id)
    {
        $user = Auth::user();
        $validated = $request->validated();

        // リクエスト→DBカラムへマッピング
        $payload = [
            'postal_code'   => $validated['post_code'],
            'address'       => $validated['address'],
            'building_name' => $validated['building_name'] ?? null,
            'is_default'    => true, // 最初の住所 or 更新を常にデフォルトにするなら
        ];

        // 既存レコードがあれば更新、なければ作成
        $address = $user->addresses()->first();
        if ($address) {
            $address->update($payload);
        } else {
            $user->addresses()->create($payload);
        }

        return redirect()
            ->route('purchase.create', ['item_id' => $item_id])
            ->with('success', '送付先住所を変更しました。');
    }
}