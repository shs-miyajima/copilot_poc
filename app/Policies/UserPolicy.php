<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /** 一覧: admin のみ */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /** 個別表示: admin のみ */
    public function view(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /** 作成: admin のみ */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /** 更新: admin のみ（自分自身のロール変更は禁止） */
    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /** 有効/無効切り替え: admin のみ（自分自身は不可） */
    public function toggleActive(User $user, User $model): bool
    {
        return $user->isAdmin() && $user->id !== $model->id;
    }
}
