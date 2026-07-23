<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * 指定したロールのいずれかを持つユーザーのみ通過させる
     *
     * 使用例:
     *   middleware('role:admin')
     *   middleware('role:admin,editor')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // アカウントが無効化されている場合はログアウト
        if (! $user->is_active) {
            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'このアカウントは無効化されています。']);
        }

        // 指定ロールのいずれかに一致するか確認
        if (! empty($roles) && ! in_array($user->role, $roles, true)) {
            abort(403, 'このページにアクセスする権限がありません。');
        }

        return $next($request);
    }
}
