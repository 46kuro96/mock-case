<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
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
            'content' => 'required|string|max:255', // コメント：必須・文字列・最大255文字
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => '商品コメントは必須です。',
            'content.max'      => '商品コメントは255文字以内で入力してください。',
        ];
    }
}
