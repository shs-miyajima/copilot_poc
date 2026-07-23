<?php

namespace App\Services;

use App\Models\Survey;
use Illuminate\Validation\ValidationException;

class SurveyPublishService
{
    /**
     * アンケートのステータスを変更する
     *
     * @throws ValidationException
     */
    public function changeStatus(Survey $survey, string $newStatus): void
    {
        $allowedTransitions = [
            'draft'     => ['published', 'closed'],
            'published' => ['draft', 'closed'],
            'closed'    => ['draft'],
        ];

        $current = $survey->status;

        if (! in_array($newStatus, $allowedTransitions[$current] ?? [], true)) {
            throw ValidationException::withMessages([
                'status' => "「{$this->label($current)}」から「{$this->label($newStatus)}」への変更はできません。",
            ]);
        }

        // 公開する場合は質問が1件以上必要
        if ($newStatus === 'published') {
            if ($survey->questions()->count() === 0) {
                throw ValidationException::withMessages([
                    'status' => '公開するには質問を1件以上追加してください。',
                ]);
            }
        }

        $survey->update(['status' => $newStatus]);
    }

    private function label(string $status): string
    {
        return match ($status) {
            'draft'     => '下書き',
            'published' => '公開中',
            'closed'    => '終了',
            default     => $status,
        };
    }
}
