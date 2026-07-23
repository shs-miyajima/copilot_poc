<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuestionRequest;
use App\Models\Question;
use App\Models\Survey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    /**
     * 質問を追加する
     */
    public function store(StoreQuestionRequest $request, Survey $survey): RedirectResponse
    {
        $order = $survey->questions()->max('order') + 1;

        $question = $survey->questions()->create([
            'type'        => $request->type,
            'label'       => $request->label,
            'hint'        => $request->hint,
            'is_required' => $request->boolean('is_required'),
            'order'       => $order,
            'scale_min'   => $request->scale_min,
            'scale_max'   => $request->scale_max,
        ]);

        // 選択肢タイプの場合は選択肢を保存
        if ($question->hasOptions() && $request->filled('options')) {
            foreach (array_values(array_filter($request->options)) as $i => $label) {
                $question->options()->create(['label' => $label, 'order' => $i]);
            }
        }

        return redirect()
            ->route('admin.surveys.edit', $survey)
            ->with('success', '質問を追加しました。');
    }

    /**
     * 質問を更新する
     */
    public function update(StoreQuestionRequest $request, Survey $survey, Question $question): RedirectResponse
    {
        $question->update([
            'type'        => $request->type,
            'label'       => $request->label,
            'hint'        => $request->hint,
            'is_required' => $request->boolean('is_required'),
            'scale_min'   => $request->scale_min,
            'scale_max'   => $request->scale_max,
        ]);

        // 選択肢を全削除して再作成
        if ($question->hasOptions()) {
            $question->options()->delete();
            if ($request->filled('options')) {
                foreach (array_values(array_filter($request->options)) as $i => $label) {
                    $question->options()->create(['label' => $label, 'order' => $i]);
                }
            }
        }

        return redirect()
            ->route('admin.surveys.edit', $survey)
            ->with('success', '質問を更新しました。');
    }

    /**
     * 質問を削除する
     */
    public function destroy(Survey $survey, Question $question): RedirectResponse
    {
        $this->authorize('update', $survey);

        $question->delete();

        // order を詰め直す
        $survey->questions()->orderBy('order')->get()
            ->each(fn ($q, $i) => $q->update(['order' => $i]));

        return redirect()
            ->route('admin.surveys.edit', $survey)
            ->with('success', '質問を削除しました。');
    }

    /**
     * 質問の並べ替え（AJAX）
     */
    public function reorder(Request $request, Survey $survey): JsonResponse
    {
        $this->authorize('update', $survey);

        $request->validate([
            'order'   => ['required', 'array'],
            'order.*' => ['integer'],
        ]);

        foreach ($request->order as $index => $questionId) {
            $survey->questions()
                ->where('id', $questionId)
                ->update(['order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
