<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExhibitionRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'title' => 'required|string',
            'description' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png|max:2000', // 20MBまで
            'categories' => 'required|array|min:1',
            'categories.*' => 'exists:categories,id',
            'condition' => 'required|in:like_new,good,fair,damaged',
            'price' => 'required|numeric|min:0',
        ];
    }
    public function messages(): array
    {
        return [
            'title.required' => '商品名を入力してください',
            'description.required' => '商品説明を入力してください',
            'description.max' => '商品説明は255文字以内で入力してください。',
            'image.required' => '画像をアップロードしてください',
            'image.image' => '有効な画像ファイルをアップロードしてください',
            'image.mimes' => '商品画像はJPEGまたはPNG形式でアップロードしてください。',
            'categories.required' => 'カテゴリーを選択してください',
            'categories.exists' => '選択されたカテゴリーは存在しません。',
            'categories.min' => '少なくとも1つのカテゴリーを選択してください',
            'condition.required' => '商品の状態を選択してください',
            'condition.in' => '選択された商品の状態が不正です。',
            'price.required' => '商品価格を入力してください',
            'price.numeric' => '商品価格は数値で入力してください',
            'price.min' => '商品価格は0円以上にしてください',
        ];
    }
}
