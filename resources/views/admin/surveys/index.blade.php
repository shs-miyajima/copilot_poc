@extends('layouts.admin')

@section('title', 'アンケート管理')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h2 class="text-xl font-semibold text-gray-800">アンケート一覧</h2>
    @can('create', App\Models\Survey::class)
    <a href="{{ route('admin.surveys.create') }}"
       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition duration-150">
        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        新規作成
    </a>
    @endcan
</div>

{{-- 検索・フィルタ --}}
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" action="{{ route('admin.surveys.index') }}" class="flex flex-wrap gap-3">
        <input
            type="text"
            name="search"
            value="{{ request('search') }}"
            placeholder="タイトルで検索..."
            class="flex-1 min-w-48 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
        <select name="status" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            <option value="">すべてのステータス</option>
            <option value="draft"     {{ request('status') === 'draft'     ? 'selected' : '' }}>下書き</option>
            <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>公開中</option>
            <option value="closed"    {{ request('status') === 'closed'    ? 'selected' : '' }}>終了</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm rounded-md transition duration-150">
            検索
        </button>
        @if(request('search') || request('status'))
        <a href="{{ route('admin.surveys.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 border border-gray-300 rounded-md transition duration-150">
            クリア
        </a>
        @endif
    </form>
</div>

{{-- アンケート一覧テーブル --}}
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    @if($surveys->isEmpty())
        <div class="text-center py-12 text-gray-500">
            <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p>アンケートがありません。</p>
            @can('create', App\Models\Survey::class)
            <a href="{{ route('admin.surveys.create') }}" class="mt-2 inline-block text-blue-600 hover:underline text-sm">最初のアンケートを作成する</a>
            @endcan
        </div>
    @else
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">タイトル</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ステータス</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">回答数</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">作成日</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($surveys as $survey)
                <tr class="hover:bg-gray-50 transition duration-150">
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $survey->title }}</div>
                        @if($survey->description)
                        <div class="text-xs text-gray-500 mt-1 truncate max-w-xs">{{ $survey->description }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $survey->status_badge_class }}">
                            {{ $survey->status_label }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ number_format($survey->responses_count) }}件
                        @if($survey->max_responses)
                        <span class="text-gray-400">/ {{ number_format($survey->max_responses) }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $survey->created_at->format('Y/m/d') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                        <a href="{{ route('admin.surveys.show', $survey) }}" class="text-blue-600 hover:text-blue-800">詳細</a>
                        @can('update', $survey)
                        <a href="{{ route('admin.surveys.edit', $survey) }}" class="text-indigo-600 hover:text-indigo-800">編集</a>
                        @endcan
                        @can('delete', $survey)
                        <form method="POST" action="{{ route('admin.surveys.destroy', $survey) }}" class="inline"
                              onsubmit="return confirm('このアンケートを削除しますか？')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800">削除</button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4 border-t">
            {{ $surveys->links() }}
        </div>
    @endif
</div>
@endsection
