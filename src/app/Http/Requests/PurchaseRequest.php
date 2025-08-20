<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // ログイン済みユーザーのみ許可
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            // 必須（クレジットカード or コンビニ）
            'payment_method' => 'required|in:credit_card,convenience_store',

            // 必須（addresses テーブルの id が存在すること）
            'address_id' => 'required|exists:addresses,id',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => '支払い方法を選択してください。',
            'payment_method.in'       => '選択できる支払い方法は「クレジットカード」または「コンビニ」です。',

            'address_id.required' => '配送先を選択してください。',
            'address_id.exists'   => '選択した配送先は存在しません。',
        ];
    }
}