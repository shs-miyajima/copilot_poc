@extends('layouts.admin')

@section('title', $survey->title)

@section('content')
<div class="mb-6 flex items-center justify-between">
    <a href="{{ route('admin.surveys.index') }}" class="text-sm text-blue-600 hover:text-blue-800">&larr; 一覧に戻る</a>
    <div class="flex items-center space-x-2">
        @can('update', $survey)
        <a href="{{ route('admin.surveys.edit', $survey) }}"
           class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-md transition duration-150">
            編集
        </a>
        @endcan
        @can('duplicate', $survey)
        <form method="POST" action="{{ route('admin.surveys.duplicate', $survey) }}" class="inline">
            @csrf
            <button type="submit" class="px-3 py-1.5 border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm rounded-md transition duration-150">
                複製
            </button>
        </form>
        @endcan
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- メイン情報 --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-lg shadow-sm p-6">
            <div class="flex items-start justify-between mb-4">
                <h2 class="text-xl font-bold text-gray-900">{{ $survey->title }}</h2>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $survey->status_badge_class }}">
                    {{ $survey->status_label }}
                </span>
            </div>
            @if($survey->description)
            <p class="text-gray-600 text-sm mb-4">{{ $survey->description }}</p>
            @endif

            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-gray-500">作成者</dt>
                    <dd class="font-medium text-gray-900">{{ $survey->user->name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500">回答数</dt>
                    <dd class="font-medium text-gray-900">{{ number_format($survey->responses_count) }}件
                        @if($survey->max_responses)
                        <span class="text-gray-400 font-normal">/ {{ number_format($survey->max_responses) }}</span>
                        @endif
                    </dd>
                </div>
                @if($survey->starts_at)
                <div>
                    <dt class="text-gray-500">公開開始</dt>
                    <dd class="font-medium text-gray-900">{{ $survey->starts_at->format('Y/m/d H:i') }}</dd>
                </div>
                @endif
                @if($survey->ends_at)
                <div>
                    <dt class="text-gray-500">公開終了</dt>
                    <dd class="font-medium text-gray-900">{{ $survey->ends_at->format('Y/m/d H:i') }}</dd>
                </div>
                @endif
            </dl>
        </div>

        {{-- 質問一覧 --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">質問一覧（{{ $survey->questions->count() }}件）</h3>
            @forelse($survey->questions as $i => $question)
            <div class="border border-gray-100 rounded-md p-4 mb-3 last:mb-0">
                <div class="flex items-start justify-between">
                    <div>
                        <span class="text-xs text-gray-400 mr-2">Q{{ $i + 1 }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-50 text-blue-700 mr-2">
                            {{ $question->type_label }}
                        </span>
                        @if($question->is_required)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-50 text-red-700">必須</span>
                        @endif
                    </div>
                </div>
                <p class="mt-2 text-sm text-gray-800">{{ $question->label }}</p>
                @if($question->hasOptions())
                <ul class="mt-2 space-y-1">
                    @foreach($question->options as $option)
                    <li class="text-xs text-gray-500 ml-4">・{{ $option->label }}</li>
                    @endforeach
                </ul>
                @endif
            </div>
            @empty
            <p class="text-sm text-gray-500">質問がありません。</p>
            @endforelse
        </div>
    </div>

    {{-- サイドバー --}}
    <div class="space-y-6">
        {{-- ステータス変更 --}}
        @can('updateStatus', $survey)
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">ステータス変更</h3>
            <form method="POST" action="{{ route('admin.surveys.updateStatus', $survey) }}">
                @csrf @method('PATCH')
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="draft"     {{ $survey->status === 'draft'     ? 'selected' : '' }}>下書き</option>
                    <option value="published" {{ $survey->status === 'published' ? 'selected' : '' }}>公開中</option>
                    <option value="closed"    {{ $survey->status === 'closed'    ? 'selected' : '' }}>終了</option>
                </select>
                @error('status')
                    <p class="mb-2 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition duration-150">
                    変更する
                </button>
            </form>
        </div>
        @endcan

        {{-- 公開URL --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-4">公開URL</h3>
            <div class="bg-gray-50 rounded-md p-3 break-all text-xs text-gray-700 mb-3 font-mono">
                {{ $survey->public_url }}
            </div>
            <button onclick="navigator.clipboard.writeText('{{ $survey->public_url }}').then(() => alert('コピーしました'))"
                    class="w-full px-4 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 text-sm rounded-md transition duration-150">
                URLをコピー
            </button>
            @if($survey->status === 'published')
            <a href="{{ $survey->public_url }}" target="_blank"
               class="mt-2 w-full inline-block text-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm rounded-md transition duration-150">
                フォームを開く
            </a>
            @endif
        </div>

        {{-- 結果確認 --}}
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-800 mb-3">回答結果</h3>
            <a href="{{ route('admin.surveys.results.index', $survey) }}"
               class="w-full inline-block text-center px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm rounded-md transition duration-150">
                結果を確認する
            </a>
        </div>
    </div>
</div>
@endsection
