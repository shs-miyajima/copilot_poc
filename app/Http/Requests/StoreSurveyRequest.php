<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSurveyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isEditorOrAbove();
    }

    public function rules(): array
    {
        return [
            'title'               => ['required', 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
            'starts_at'           => ['nullable', 'date'],
            'ends_at'             => ['nullable', 'date', 'after_or_equal:starts_at'],
            'max_responses'       => ['nullable', 'integer', 'min:1'],
            'response_limit_type' => ['required', 'in:none,cookie,ip'],
            'thanks_message'      => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'          => 'タイトルは必須です。',
            'ends_at.after_or_equal'  => '終了日時は開始日時より後に設定してください。',
            'max_responses.min'       => '回答上限は1以上の値を入力してください。',
        ];
    }
}
