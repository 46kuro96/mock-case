<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function login(LoginRequest $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
        // ログイン成功
            return redirect()->intended('/'); // ホームへリダイレクト
        }

        // ログイン失敗
        return back()->withErrors([
            'login' => 'ログイン情報が登録されていません',
        ])->withInput();
    }

    // ログイン画面表示
    public function showLoginForm()
    {
        return view('auth.login');
    }

        public function logout(Request $request)
    {
        Auth::logout(); // ログアウト
        $request->session()->invalidate(); // セッション無効化
        $request->session()->regenerateToken(); // CSRFトークン再生成

        return redirect('/'); // ログアウト後のリダイレクト先
    }
}
