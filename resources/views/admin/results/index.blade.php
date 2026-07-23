@extends('layouts.admin')

@section('title', '集計結果: ' . $survey->title)

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
@endpush

@section('content')
{{-- ヘッダー --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <a href="{{ route('admin.surveys.show', $survey) }}" class="text-sm text-blue-600 hover:underline">&larr; アンケート詳細に戻る</a>
        <h2 class="text-xl font-bold text-gray-800 mt-1">{{ $survey->title }}</h2>
    </div>
    <div class="flex gap-2">
        <a href="{{ route('admin.surveys.results.export.csv', $survey) }}"
           class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
            </svg>
            CSVエクスポート
        </a>
        <a href="{{ route('admin.surveys.results.export.excel', $survey) }}"
           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Excelエクスポート
        </a>
    </div>
</div>

{{-- サマリーカード --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-100">
        <p class="text-sm text-gray-500">総回答数</p>
        <p class="text-3xl font-bold text-gray-800 mt-1">{{ $statistics['summary']['total_responses'] }}</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-100">
        <p class="text-sm text-gray-500">質問数</p>
        <p class="text-3xl font-bold text-gray-800 mt-1">{{ $survey->questions->count() }}</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-5 border border-gray-100">
        <p class="text-sm text-gray-500">ステータス</p>
        <span class="inline-flex items-center mt-1 px-3 py-1 rounded-full text-sm font-medium {{ $survey->status_badge_class }}">
            {{ $survey->status_label }}
        </span>
    </div>
</div>

{{-- 質問ごとの集計 --}}
@foreach($statistics['questions'] as $qStat)
<div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6 mb-6">
    <h3 class="text-base font-semibold text-gray-800 mb-1">{{ $qStat['label'] }}</h3>
    <p class="text-sm text-gray-500 mb-4">
        回答数: {{ $qStat['answered_count'] }}
        @if(isset($qStat['total']) && $qStat['total'] > 0)
            （回答率: {{ round($qStat['answered_count'] / $qStat['total'] * 100) }}%）
        @endif
        @if(isset($qStat['average']))
            &nbsp;｜&nbsp; 平均: <strong>{{ $qStat['average'] }}</strong>
        @endif
    </p>

    @if(in_array($qStat['type'], ['radio', 'checkbox', 'dropdown', 'rating']) && !empty($qStat['options']))
        {{-- グラフ + 表 --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="relative h-64">
                <canvas id="chart-{{ $qStat['question_id'] }}"></canvas>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="text-left px-3 py-2 text-gray-600">選択肢</th>
                            <th class="text-right px-3 py-2 text-gray-600">件数</th>
                            <th class="text-right px-3 py-2 text-gray-600">割合</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($qStat['options'] as $opt)
                        <tr>
                            <td class="px-3 py-2 text-gray-700">{{ $opt['label'] }}</td>
                            <td class="px-3 py-2 text-right text-gray-700">{{ $opt['count'] }}</td>
                            <td class="px-3 py-2 text-right text-gray-700">{{ $opt['percentage'] }}%</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    @elseif(in_array($qStat['type'], ['text', 'textarea']) && !empty($qStat['values']))
        {{-- テキスト回答一覧 --}}
        <div class="space-y-2 max-h-64 overflow-y-auto">
            @foreach($qStat['values'] as $val)
            <div class="bg-gray-50 rounded px-3 py-2 text-sm text-gray-700 whitespace-pre-line">{{ $val }}</div>
            @endforeach
        </div>

    @else
        <p class="text-sm text-gray-400">回答データがありません。</p>
    @endif
</div>
@endforeach

{{-- 回答一覧テーブル --}}
<div class="bg-white rounded-lg shadow-sm border border-gray-100 p-6">
    <h3 class="text-base font-semibold text-gray-800 mb-4">回答一覧</h3>

    @if($responses->isEmpty())
        <p class="text-sm text-gray-400">回答データがありません。</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="text-left px-3 py-2 text-gray-600 whitespace-nowrap">回答ID</th>
                        <th class="text-left px-3 py-2 text-gray-600 whitespace-nowrap">回答日時</th>
                        @foreach($survey->questions as $q)
                            <th class="text-left px-3 py-2 text-gray-600 max-w-xs">{{ Str::limit($q->label, 20) }}</th>
                        @endforeach
                        <th class="px-3 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($responses as $response)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 text-gray-500">#{{ $response->id }}</td>
                        <td class="px-3 py-2 text-gray-600 whitespace-nowrap">{{ $response->submitted_at->format('Y/m/d H:i') }}</td>
                        @foreach($survey->questions as $q)
                            @php
                                $answer = $response->answers->firstWhere('question_id', $q->id);
                                $displayVal = '';
                                if ($answer) {
                                    if (in_array($q->type, ['radio', 'dropdown'])) {
                                        $opt = $q->options->firstWhere('id', $answer->value);
                                        $displayVal = $opt ? $opt->label : $answer->value;
                                    } elseif ($q->type === 'checkbox') {
                                        $selIds = $answer->options->pluck('option_id')->toArray();
                                        $displayVal = $q->options->filter(fn($o) => in_array($o->id, $selIds))->pluck('label')->implode(', ');
                                    } else {
                                        $displayVal = $answer->value;
                                    }
                                }
                            @endphp
                            <td class="px-3 py-2 text-gray-700 max-w-xs truncate" title="{{ $displayVal }}">{{ $displayVal }}</td>
                        @endforeach
                        <td class="px-3 py-2 text-right">
                            <form method="POST" action="{{ route('admin.surveys.results.destroy', [$survey, $response]) }}"
                                  onsubmit="return confirm('この回答を削除しますか？')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-red-500 hover:text-red-700">削除</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $responses->links() }}
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
const statistics = @json($statistics);

// 色パレット
const palette = [
    '#3B82F6','#10B981','#F59E0B','#EF4444','#8B5CF6',
    '#06B6D4','#F97316','#EC4899','#6366F1','#84CC16',
];

statistics.questions.forEach(q => {
    if (!['radio','checkbox','dropdown','rating'].includes(q.type)) return;
    if (!q.options || q.options.length === 0) return;

    const canvas = document.getElementById('chart-' + q.question_id);
    if (!canvas) return;

    const labels = q.options.map(o => o.label);
    const data   = q.options.map(o => o.count);
    const colors = q.options.map((_, i) => palette[i % palette.length]);

    const isBar = q.type === 'rating' || q.options.length > 5;

    new Chart(canvas, {
        type: isBar ? 'bar' : 'pie',
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: colors,
                borderWidth: 1,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: !isBar },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const opt = q.options[ctx.dataIndex];
                            return ` ${opt.count}件（${opt.percentage}%）`;
                        },
                    },
                },
            },
            scales: isBar ? {
                y: { beginAtZero: true, ticks: { stepSize: 1 } },
            } : {},
        },
    });
});
</script>
@endpush
