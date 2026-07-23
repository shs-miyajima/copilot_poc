<?php

namespace App\Policies;

use App\Models\Survey;
use App\Models\User;

class SurveyPolicy
{
    /**
     * 一覧表示：全ロールで可能
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * 個別表示：全ロールで可能
     */
    public function view(User $user, Survey $survey): bool
    {
        return true;
    }

    /**
     * 作成：admin / editor のみ
     */
    public function create(User $user): bool
    {
        return $user->isEditorOrAbove();
    }

    /**
     * 更新：admin は全て可、editor は自分のアンケートのみ
     */
    public function update(User $user, Survey $survey): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        return $user->role === 'editor' && $survey->user_id === $user->id;
    }

    /**
     * 削除：admin は全て可、editor は自分のアンケートのみ
     */
    public function delete(User $user, Survey $survey): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        return $user->role === 'editor' && $survey->user_id === $user->id;
    }

    /**
     * 複製：admin / editor のみ
     */
    public function duplicate(User $user, Survey $survey): bool
    {
        return $user->isEditorOrAbove();
    }

    /**
     * ステータス変更：update と同じルール
     */
    public function updateStatus(User $user, Survey $survey): bool
    {
        return $this->update($user, $survey);
    }
}
