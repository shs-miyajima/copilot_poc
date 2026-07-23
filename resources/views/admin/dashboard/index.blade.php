@extends('layouts.admin')

@section('title', 'ダッシュボード')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">アンケート総数</p>
                <p class="text-2xl font-semibold text-gray-900">{{ number_format($totalSurveys) }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">公開中</p>
                <p class="text-2xl font-semibold text-gray-900">{{ number_format($publishedSurveys) }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0 bg-purple-100 rounded-md p-3">
                <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">総回答数</p>
                <p class="text-2xl font-semibold text-gray-900">{{ number_format($totalResponses) }}</p>
            </div>
        </div>
    </div>
</div>

{{-- 最近のアンケート --}}
<div class="bg-white rounded-lg shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b flex items-center justify-between">
        <h2 class="text-base font-semibold text-gray-800">最近のアンケート</h2>
        <a href="{{ route('admin.surveys.index') }}" class="text-sm text-blue-600 hover:text-blue-800">すべて見る</a>
    </div>
    @if($recentSurveys->isEmpty())
        <div class="text-center py-10 text-gray-500 text-sm">
            アンケートがありません。
            @can('create', App\Models\Survey::class)
            <a href="{{ route('admin.surveys.create') }}" class="text-blue-600 hover:underline">最初のアンケートを作成する</a>
            @endcan
        </div>
    @else
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">タイトル</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ステータス</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">回答数</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">操作</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($recentSurveys as $survey)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $survey->title }}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $survey->status_badge_class }}">
                            {{ $survey->status_label }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-600">{{ number_format($survey->responses_count) }}件</td>
                    <td class="px-6 py-4 text-right text-sm">
                        <a href="{{ route('admin.surveys.show', $survey) }}" class="text-blue-600 hover:text-blue-800">詳細</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
