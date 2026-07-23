@extends('layouts.survey')

@section('title', $survey->title)

@section('content')
<div class="bg-white rounded-lg shadow-md p-6 md:p-8">
    {{-- アンケートタイトル・説明 --}}
    <h1 class="text-2xl font-bold text-gray-800 mb-2">{{ $survey->title }}</h1>
    @if($survey->description)
        <p class="text-gray-600 mb-6 whitespace-pre-line">{{ $survey->description }}</p>
    @endif

    {{-- エラーメッセージ --}}
    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6" role="alert">
            <p class="font-medium mb-1">入力内容をご確認ください。</p>
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('survey.submit', ['token' => $survey->public_token]) }}" novalidate>
        @csrf

        <div class="space-y-8" id="questions-container"
             x-data="skipLogic({{ json_encode(
                 $survey->questions->flatMap(fn($q) => $q->logics->map(fn($l) => [
                     'questionId' => $q->id,
                     'optionId'   => $l->option_id,
                     'targetId'   => $l->target_question_id,
                     'action'     => $l->action,
                 ]))->values()
             ) }})">

            @foreach($survey->questions as $question)
            <div class="question-block border-b border-gray-100 pb-8 last:border-0"
                 id="question-{{ $question->id }}"
                 x-show="isVisible({{ $question->id }})"
                 x-transition>

                {{-- 質問文 --}}
                <label class="block text-base font-medium text-gray-800 mb-1">
                    {{ $question->label }}
                    @if($question->is_required)
                        <span class="text-red-500 ml-1" aria-label="必須">*</span>
                    @endif
                </label>

                @if($question->hint)
                    <p class="text-sm text-gray-500 mb-3">{{ $question->hint }}</p>
                @endif

                {{-- 質問タイプ別入力フィールド --}}
                @switch($question->type)

                    @case('radio')
                        <div class="space-y-2">
                            @foreach($question->options as $option)
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="radio"
                                    name="answers[{{ $question->id }}]"
                                    value="{{ $option->id }}"
                                    class="h-4 w-4 text-blue-600 border-gray-300"
                                    {{ old('answers.' . $question->id) == $option->id ? 'checked' : '' }}
                                    @if($question->is_required) required @endif
                                    @change="onOptionChange({{ $question->id }}, {{ $option->id }})"
                                >
                                <span class="text-gray-700">{{ $option->label }}</span>
                            </label>
                            @endforeach
                        </div>
                        @break

                    @case('checkbox')
                        <div class="space-y-2">
                            @foreach($question->options as $option)
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="answers[{{ $question->id }}][]"
                                    value="{{ $option->id }}"
                                    class="h-4 w-4 text-blue-600 border-gray-300 rounded"
                                    {{ in_array($option->id, (array) old('answers.' . $question->id, [])) ? 'checked' : '' }}
                                >
                                <span class="text-gray-700">{{ $option->label }}</span>
                            </label>
                            @endforeach
                        </div>
                        @break

                    @case('dropdown')
                        <select
                            name="answers[{{ $question->id }}]"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            @if($question->is_required) required @endif
                            @change="onOptionChange({{ $question->id }}, parseInt($event.target.value))"
                        >
                            <option value="">-- 選択してください --</option>
                            @foreach($question->options as $option)
                                <option value="{{ $option->id }}"
                                    {{ old('answers.' . $question->id) == $option->id ? 'selected' : '' }}>
                                    {{ $option->label }}
                                </option>
                            @endforeach
                        </select>
                        @break

                    @case('text')
                        <input
                            type="text"
                            name="answers[{{ $question->id }}]"
                            value="{{ old('answers.' . $question->id) }}"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            @if($question->is_required) required @endif
                            maxlength="1000"
                        >
                        @break

                    @case('textarea')
                        <textarea
                            name="answers[{{ $question->id }}]"
                            rows="4"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            @if($question->is_required) required @endif
                            maxlength="5000"
                        >{{ old('answers.' . $question->id) }}</textarea>
                        @break

                    @case('number')
                        <input
                            type="number"
                            name="answers[{{ $question->id }}]"
                            value="{{ old('answers.' . $question->id) }}"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            @if($question->is_required) required @endif
                        >
                        @break

                    @case('date')
                        <input
                            type="date"
                            name="answers[{{ $question->id }}]"
                            value="{{ old('answers.' . $question->id) }}"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            @if($question->is_required) required @endif
                        >
                        @break

                    @case('rating')
                        @php
                            $min = $question->scale_min ?? 1;
                            $max = $question->scale_max ?? 5;
                            $oldVal = old('answers.' . $question->id);
                        @endphp
                        <div class="flex flex-wrap gap-2">
                            @for($i = $min; $i <= $max; $i++)
                            <label class="cursor-pointer">
                                <input
                                    type="radio"
                                    name="answers[{{ $question->id }}]"
                                    value="{{ $i }}"
                                    class="sr-only peer"
                                    {{ $oldVal == $i ? 'checked' : '' }}
                                    @if($question->is_required) required @endif
                                >
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full border-2 border-gray-300 text-gray-600 text-sm font-medium peer-checked:bg-blue-600 peer-checked:border-blue-600 peer-checked:text-white hover:border-blue-400 transition-colors">
                                    {{ $i }}
                                </span>
                            </label>
                            @endfor
                        </div>
                        <p class="text-xs text-gray-400 mt-1">{{ $min }} = 最低　{{ $max }} = 最高</p>
                        @break

                @endswitch

                {{-- フィールドエラー --}}
                @error('answers.' . $question->id)
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>
            @endforeach
        </div>

        <div class="mt-8">
            <button
                type="submit"
                class="w-full md:w-auto bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-8 rounded-md transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                回答を送信する
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function skipLogic(logics) {
    return {
        // 非表示になっている質問IDのセット
        hiddenQuestions: new Set(),

        onOptionChange(questionId, optionId) {
            // この質問に紐づくロジックを処理
            logics.filter(l => l.questionId === questionId).forEach(logic => {
                if (logic.action === 'skip') {
                    if (logic.optionId === optionId) {
                        this.hiddenQuestions.add(logic.targetId);
                    } else {
                        this.hiddenQuestions.delete(logic.targetId);
                    }
                }
            });
        },

        isVisible(questionId) {
            return !this.hiddenQuestions.has(questionId);
        },
    };
}
</script>
@endpush
