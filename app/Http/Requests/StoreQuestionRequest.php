<?php

namespace App\Http\Requests;

use App\Models\Question;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $survey = $this->route('survey');
        return $this->user()->can('update', $survey);
    }

    public function rules(): array
    {
        return [
            'type'      => ['required', Rule::in(array_keys(Question::TYPES))],
            'label'     => ['required', 'string', 'max:1000'],
            'hint'      => ['nullable', 'string', 'max:500'],
            'is_required' => ['boolean'],
            'scale_min' => ['nullable', 'integer', 'min:1'],
            'scale_max' => ['nullable', 'integer', 'min:2', 'gt:scale_min'],
            'options'   => ['nullable', 'array'],
            'options.*' => ['string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required'  => '質問タイプは必須です。',
            'type.in'        => '無効な質問タイプです。',
            'label.required' => '質問文は必須です。',
            'scale_max.gt'   => 'スケール最大値は最小値より大きい値を入力してください。',
        ];
    }
}
