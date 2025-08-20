<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // 認証済みユーザーのみ許可
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
            // プロフィール画像: jpeg / png のみ
            'profile_image' => 'nullable|file|mimes:jpeg,png|max:2048',

            // 名前: 入力必須
            'name' => 'required|string|max:255',

            // 郵便番号: 入力必須、ハイフンありの8文字 (例: 123-4567)
            'post_code' => ['required','regex:/^\d{3}-\d{4}$/'],

            // 住所: 入力必須
            'address' => 'required|string|max:255',

            // 建物名: 任意
            'building_name' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'profile_image.mimes' => 'プロフィール画像は .jpeg または .png 形式でアップロードしてください。',
            'profile_image.max'   => 'プロフィール画像は2MB以下にしてください。',

            'name.required'       => 'お名前は必須です。',
            'name.max'            => 'お名前は255文字以内で入力してください。',

            'post_code.required'  => '郵便番号は必須です。',
            'post_code.regex'     => '郵便番号は「123-4567」の形式で入力してください。',

            'address.required'    => '住所は必須です。',
            'address.max'         => '住所は255文字以内で入力してください。',

            'building_name.max'   => '建物名は255文字以内で入力してください。',
        ];
    }
}
