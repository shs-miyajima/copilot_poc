<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * ユーザー一覧
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $query = User::query();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->latest()->paginate(20)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    /**
     * ユーザー作成フォーム
     */
    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('admin.users.create');
    }

    /**
     * ユーザー保存
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'ユーザーを作成しました。');
    }

    /**
     * ユーザー編集フォーム
     */
    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.users.edit', compact('user'));
    }

    /**
     * ユーザー更新
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = [
            'name'      => $request->name,
            'email'     => $request->email,
            'role'      => $request->role,
            'is_active' => $request->boolean('is_active', true),
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'ユーザー情報を更新しました。');
    }

    /**
     * 有効/無効切り替え
     */
    public function toggleActive(User $user): RedirectResponse
    {
        $this->authorize('toggleActive', $user);

        $user->update(['is_active' => ! $user->is_active]);

        $label = $user->is_active ? '有効' : '無効';

        return back()->with('success', "ユーザーを{$label}にしました。");
    }
}
