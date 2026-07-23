<?php

namespace App\Http\Requests;

use App\Models\Survey;
use Illuminate\Foundation\Http\FormRequest;

class StoreResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // 認証不要（一般公開）
    }

    public function rules(): array
    {
        /** @var Survey $survey */
        $survey = $this->route('survey');
        $rules  = [];

        foreach ($survey->questions()->with('options')->get() as $question) {
            $key        = 'answers.' . $question->id;
            $fieldRules = [];

            if ($question->is_required) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            switch ($question->type) {
                case 'radio':
                case 'dropdown':
                    $fieldRules[] = 'integer';
                    $optionIds    = $question->options->pluck('id')->toArray();
                    if (! empty($optionIds)) {
                        $fieldRules[] = 'in:' . implode(',', $optionIds);
                    }
                    break;

                case 'checkbox':
                    $fieldRules   = $question->is_required ? ['required'] : ['nullable'];
                    $fieldRules[] = 'array';
                    $rules[$key . '.*'] = ['integer'];
                    $optionIds = $question->options->pluck('id')->toArray();
                    if (! empty($optionIds)) {
                        $rules[$key . '.*'][] = 'in:' . implode(',', $optionIds);
                    }
                    break;

                case 'text':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:1000';
                    break;

                case 'textarea':
                    $fieldRules[] = 'string';
                    $fieldRules[] = 'max:5000';
                    break;

                case 'number':
                    $fieldRules[] = 'numeric';
                    break;

                case 'rating':
                    $fieldRules[] = 'integer';
                    $min = $question->scale_min ?? 1;
                    $max = $question->scale_max ?? 5;
                    $fieldRules[] = "min:{$min}";
                    $fieldRules[] = "max:{$max}";
                    break;

                case 'date':
                    $fieldRules[] = 'date';
                    break;
            }

            $rules[$key] = $fieldRules;
        }

        return $rules;
    }

    public function attributes(): array
    {
        /** @var Survey $survey */
        $survey     = $this->route('survey');
        $attributes = [];

        foreach ($survey->questions as $question) {
            $attributes['answers.' . $question->id] = $question->label;
        }

        return $attributes;
    }

    public function messages(): array
    {
        return [
            'answers.*.required' => ':attributeは必須です。',
            'answers.*.in'       => ':attributeの選択肢が不正です。',
            'answers.*.max'      => ':attributeは:max文字以内で入力してください。',
            'answers.*.min'      => ':attributeは:min以上の値を入力してください。',
            'answers.*.date'     => ':attributeは有効な日付を入力してください。',
            'answers.*.numeric'  => ':attributeは数値を入力してください。',
        ];
    }
}
