<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'post_code'     => ['required','regex:/^\d{3}-\d{4}$/'],   // 郵便番号：必須・ハイフンあり8文字（例: 123-4567）
            'address'       => 'required|string|max:255',              // 住所：必須
            'building_name' => 'nullable|string|max:255',              // 建物名：任意
        ];
    }

    public function messages()
    {
        return [
            'post_code.required'  => '郵便番号は必須です。',
            'post_code.regex'     => '郵便番号は「123-4567」の形式で入力してください。',
            'address.required'    => '住所は必須です。',
        ];
    }
}
