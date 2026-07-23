<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Response;
use App\Models\Survey;
use App\Services\ExportService;
use App\Services\StatisticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ResultController extends Controller
{
    public function __construct(
        private StatisticsService $statisticsService,
        private ExportService $exportService,
    ) {}

    /**
     * 集計結果・回答一覧を表示する
     */
    public function index(Request $request, Survey $survey): \Illuminate\View\View
    {
        $this->authorize('view', $survey);

        $survey->load(['questions.options']);

        $statistics = $this->statisticsService->summarizeAll($survey);

        $responses = $survey->responses()
            ->with(['answers.options'])
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->paginate(50);

        return view('admin.results.index', compact('survey', 'statistics', 'responses'));
    }

    /**
     * CSV エクスポート
     */
    public function exportCsv(Survey $survey): StreamedResponse
    {
        $this->authorize('view', $survey);

        $survey->load(['questions.options']);

        return $this->exportService->exportCsv($survey);
    }

    /**
     * Excel エクスポート
     */
    public function exportExcel(Survey $survey): StreamedResponse
    {
        $this->authorize('view', $survey);

        $survey->load(['questions.options']);

        return $this->exportService->exportExcel($survey);
    }

    /**
     * 回答を個別削除する
     */
    public function destroyResponse(Survey $survey, Response $response): RedirectResponse
    {
        $this->authorize('update', $survey);

        // 対象の回答がこのアンケートに属しているか確認
        abort_if($response->survey_id !== $survey->id, 404);

        $response->delete();

        // キャッシュをクリア
        \Illuminate\Support\Facades\Cache::forget("survey_all_{$survey->id}");
        \Illuminate\Support\Facades\Cache::forget("survey_summary_{$survey->id}");

        return back()->with('success', '回答を削除しました。');
    }
}
