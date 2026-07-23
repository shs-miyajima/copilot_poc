<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSurveyRequest;
use App\Http\Requests\UpdateSurveyRequest;
use App\Models\Survey;
use App\Services\SurveyPublishService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SurveyController extends Controller
{
    public function __construct(private SurveyPublishService $publishService) {}

    /**
     * アンケート一覧
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Survey::class);

        $query = Survey::with('user')
            ->withCount('responses');

        // ステータスフィルタ
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // キーワード検索
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // viewer は全アンケート閲覧可、editor は自分のアンケートのみ
        if (Auth::user()->role === 'editor') {
            $query->where('user_id', Auth::id());
        }

        $surveys = $query->latest()->paginate(20)->withQueryString();

        return view('admin.surveys.index', compact('surveys'));
    }

    /**
     * アンケート作成フォーム
     */
    public function create(): View
    {
        $this->authorize('create', Survey::class);

        return view('admin.surveys.create');
    }

    /**
     * アンケート保存
     */
    public function store(StoreSurveyRequest $request): RedirectResponse
    {
        $survey = Survey::create([
            ...$request->validated(),
            'user_id' => Auth::id(),
        ]);

        return redirect()
            ->route('admin.surveys.edit', $survey)
            ->with('success', 'アンケートを作成しました。質問を追加してください。');
    }

    /**
     * アンケート詳細（公開URL表示）
     */
    public function show(Survey $survey): View
    {
        $this->authorize('view', $survey);

        $survey->load(['questions.options', 'user']);
        $survey->loadCount('responses');

        return view('admin.surveys.show', compact('survey'));
    }

    /**
     * アンケート編集フォーム
     */
    public function edit(Survey $survey): View
    {
        $this->authorize('update', $survey);

        $survey->load(['questions.options']);

        return view('admin.surveys.edit', compact('survey'));
    }

    /**
     * アンケート更新
     */
    public function update(UpdateSurveyRequest $request, Survey $survey): RedirectResponse
    {
        $survey->update($request->validated());

        return redirect()
            ->route('admin.surveys.edit', $survey)
            ->with('success', 'アンケートを保存しました。');
    }

    /**
     * アンケート論理削除
     */
    public function destroy(Survey $survey): RedirectResponse
    {
        $this->authorize('delete', $survey);

        $survey->delete();

        return redirect()
            ->route('admin.surveys.index')
            ->with('success', 'アンケートを削除しました。');
    }

    /**
     * アンケート複製
     */
    public function duplicate(Survey $survey): RedirectResponse
    {
        $this->authorize('duplicate', $survey);

        $newSurvey = $survey->replicate(['public_token', 'deleted_at']);
        $newSurvey->title  = $survey->title . '（コピー）';
        $newSurvey->status = 'draft';
        $newSurvey->save();

        // 質問と選択肢を複製
        foreach ($survey->questions()->with('options')->get() as $question) {
            $newQuestion = $question->replicate();
            $newQuestion->survey_id = $newSurvey->id;
            $newQuestion->save();

            foreach ($question->options as $option) {
                $newOption = $option->replicate();
                $newOption->question_id = $newQuestion->id;
                $newOption->save();
            }
        }

        return redirect()
            ->route('admin.surveys.edit', $newSurvey)
            ->with('success', 'アンケートを複製しました。');
    }

    /**
     * ステータス変更
     */
    public function updateStatus(Request $request, Survey $survey): RedirectResponse
    {
        $this->authorize('updateStatus', $survey);

        $request->validate([
            'status' => ['required', 'in:draft,published,closed'],
        ]);

        try {
            $this->publishService->changeStatus($survey, $request->status);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->with('error', $e->getMessage());
        }

        $labels = ['draft' => '下書き', 'published' => '公開中', 'closed' => '終了'];
        return back()->with('success', "ステータスを「{$labels[$request->status]}」に変更しました。");
    }
}
