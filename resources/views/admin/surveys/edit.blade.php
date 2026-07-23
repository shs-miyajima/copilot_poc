@extends('layouts.admin')

@section('title', 'アンケート編集')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <a href="{{ route('admin.surveys.show', $survey) }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; 詳細に戻る</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- 基本情報編集 --}}
    <div class="bg-white rounded-lg shadow-sm p-6">
        <h2 class="text-base font-semibold text-gray-800 mb-4">基本情報</h2>
        <form method="POST" action="{{ route('admin.surveys.update', $survey) }}">
            @csrf @method('PUT')
            @include('admin.surveys._form')
            <div class="mt-5 flex justify-end">
                <button type="submit"
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition duration-150">
                    保存する
                </button>
            </div>
        </form>
    </div>

    {{-- 質問ビルダー --}}
    <div class="space-y-6" x-data="questionBuilder({{ json_encode($survey->questions->map(fn($q) => [
        'id'          => $q->id,
        'type'        => $q->type,
        'label'       => $q->label,
        'hint'        => $q->hint,
        'is_required' => $q->is_required,
        'scale_min'   => $q->scale_min ?? 1,
        'scale_max'   => $q->scale_max ?? 5,
        'options'     => $q->options->pluck('label')->toArray(),
        'open'        => false,
    ])->values()->toJson()) }}, {{ $survey->id }})">

        {{-- 既存質問リスト --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-base font-semibold text-gray-800 mb-4">
                質問一覧
                <span class="text-sm font-normal text-gray-500 ml-2" x-text="`(${questions.length}件)`"></span>
            </h2>

            <div id="questions-sortable" class="space-y-3 min-h-8">
                <template x-for="(q, index) in questions" :key="q.id">
                    <div class="border border-gray-200 rounded-md" :data-id="q.id">
                        {{-- 質問ヘッダー --}}
                        <div class="flex items-center px-4 py-3 cursor-pointer select-none"
                             @click="q.open = !q.open">
                            <span class="cursor-grab mr-3 text-gray-400 handle">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                </svg>
                            </span>
                            <span class="text-xs text-gray-400 mr-2" x-text="`Q${index + 1}`"></span>
                            <span class="flex-1 text-sm font-medium text-gray-800 truncate" x-text="q.label || '（未入力）'"></span>
                            <span class="text-xs text-blue-600 bg-blue-50 px-2 py-0.5 rounded mr-2"
                                  x-text="typeLabels[q.type] || q.type"></span>
                            <span x-show="q.is_required" class="text-xs text-red-600 bg-red-50 px-2 py-0.5 rounded mr-2">必須</span>
                            <svg class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="q.open ? 'rotate-180' : ''"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>

                        {{-- 質問編集パネル --}}
                        <div x-show="q.open" x-collapse class="border-t border-gray-200 px-4 py-4">
                            <form :action="`/admin/surveys/{{ $survey->id }}/questions/${q.id}`" method="POST">
                                @csrf
                                <input type="hidden" name="_method" value="PUT">
                                @include('admin.surveys._question_fields')
                                <div class="mt-4 flex justify-between">
                                    <button type="button"
                                            @click="deleteQuestion(q.id)"
                                            class="text-sm text-red-600 hover:text-red-800">
                                        削除
                                    </button>
                                    <button type="submit"
                                            class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-md transition duration-150">
                                        更新
                                    </button>
                                </div>
                            </form>
                            {{-- 削除フォーム（hidden） --}}
                            <form :id="`delete-form-${q.id}`"
                                  :action="`/admin/surveys/{{ $survey->id }}/questions/${q.id}`"
                                  method="POST" class="hidden">
                                @csrf
                                <input type="hidden" name="_method" value="DELETE">
                            </form>
                        </div>
                    </div>
                </template>

                <div x-show="questions.length === 0" class="text-center py-8 text-gray-400 text-sm border-2 border-dashed border-gray-200 rounded-md">
                    質問がありません。下のフォームから追加してください。
                </div>
            </div>
        </div>

        {{-- 質問追加フォーム --}}
        <div class="bg-white rounded-lg shadow-sm p-6" x-data="newQuestionForm()">
            <h2 class="text-base font-semibold text-gray-800 mb-4">質問を追加</h2>
            <form method="POST" action="{{ route('admin.surveys.questions.store', $survey) }}">
                @csrf
                @include('admin.surveys._question_fields')
                <div class="mt-4 flex justify-end">
                    <button type="submit"
                            class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition duration-150">
                        質問を追加する
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
function questionBuilder(initialQuestions, surveyId) {
    return {
        questions: initialQuestions,
        surveyId: surveyId,
        typeLabels: @json(\App\Models\Question::TYPES),

        init() {
            this.$nextTick(() => {
                const el = document.getElementById('questions-sortable');
                if (el) {
                    Sortable.create(el, {
                        handle: '.handle',
                        animation: 150,
                        onEnd: (evt) => {
                            // DOM順序に合わせて questions 配列を並べ替え
                            const ids = [...el.querySelectorAll('[data-id]')].map(el => parseInt(el.dataset.id));
                            this.questions = ids.map(id => this.questions.find(q => q.id === id));
                            this.saveOrder(ids);
                        }
                    });
                }
            });
        },

        saveOrder(ids) {
            fetch(`/admin/surveys/${this.surveyId}/questions/reorder`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ order: ids }),
            });
        },

        deleteQuestion(id) {
            if (!confirm('この質問を削除しますか？')) return;
            document.getElementById(`delete-form-${id}`).submit();
        }
    };
}

function newQuestionForm() {
    return {
        type: 'text',
        options: [''],
        typeLabels: @json(\App\Models\Question::TYPES),
        optionTypes: @json(\App\Models\Question::OPTION_TYPES),

        get hasOptions() {
            return this.optionTypes.includes(this.type);
        },
        get isRating() {
            return this.type === 'rating';
        },
        addOption() {
            this.options.push('');
        },
        removeOption(i) {
            if (this.options.length > 1) this.options.splice(i, 1);
        }
    };
}
</script>
@endpush
