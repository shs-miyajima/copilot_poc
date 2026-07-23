<?php

namespace App\Services;

use App\Models\Survey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatisticsService
{
    /**
     * アンケート全体のサマリーを返す
     */
    public function getSurveySummary(Survey $survey): array
    {
        return Cache::remember("survey_summary_{$survey->id}", 300, function () use ($survey) {
            $totalResponses = $survey->responses()
                ->whereNotNull('submitted_at')
                ->count();

            return [
                'total_responses' => $totalResponses,
                'survey_id'       => $survey->id,
                'title'           => $survey->title,
            ];
        });
    }

    /**
     * 質問ごとの集計データを返す
     *
     * 選択系: 各選択肢の件数・割合
     * テキスト系: 回答一覧
     */
    public function summarizeQuestion(Survey $survey, int $questionId): array
    {
        return Cache::remember("survey_question_{$survey->id}_{$questionId}", 300, function () use ($survey, $questionId) {
            $question = $survey->questions()
                ->with('options')
                ->findOrFail($questionId);

            $totalResponses = $survey->responses()
                ->whereNotNull('submitted_at')
                ->count();

            if (in_array($question->type, ['radio', 'dropdown'], true)) {
                return $this->summarizeSingleChoice($question, $totalResponses);
            }

            if ($question->type === 'checkbox') {
                return $this->summarizeMultiChoice($question, $totalResponses);
            }

            if ($question->type === 'rating') {
                return $this->summarizeRating($question, $totalResponses);
            }

            // text / textarea / number / date
            return $this->summarizeText($question);
        });
    }

    /**
     * アンケート全質問の集計をまとめて返す（N+1 回避）
     */
    public function summarizeAll(Survey $survey): array
    {
        return Cache::remember("survey_all_{$survey->id}", 300, function () use ($survey) {
            $survey->load(['questions.options']);
            $summary = $this->getSurveySummary($survey);

            $questions = [];
            foreach ($survey->questions as $question) {
                $questions[] = $this->summarizeQuestion($survey, $question->id);
            }

            return [
                'summary'   => $summary,
                'questions' => $questions,
            ];
        });
    }

    // ----------------------------------------------------------------
    // 内部集計メソッド
    // ----------------------------------------------------------------

    private function summarizeSingleChoice($question, int $totalResponses): array
    {
        $counts = DB::table('answer_options')
            ->join('answers', 'answer_options.answer_id', '=', 'answers.id')
            ->join('responses', 'answers.response_id', '=', 'responses.id')
            ->where('answers.question_id', $question->id)
            ->whereNotNull('responses.submitted_at')
            ->select('answer_options.option_id', DB::raw('COUNT(*) as count'))
            ->groupBy('answer_options.option_id')
            ->pluck('count', 'option_id');

        $answeredCount = $counts->sum();
        $options = $question->options->map(function ($option) use ($counts, $answeredCount) {
            $count = $counts->get($option->id, 0);
            return [
                'id'         => $option->id,
                'label'      => $option->label,
                'count'      => $count,
                'percentage' => $answeredCount > 0 ? round($count / $answeredCount * 100, 1) : 0,
            ];
        });

        return [
            'question_id'    => $question->id,
            'label'          => $question->label,
            'type'           => $question->type,
            'answered_count' => $answeredCount,
            'total'          => $totalResponses,
            'options'        => $options->toArray(),
        ];
    }

    private function summarizeMultiChoice($question, int $totalResponses): array
    {
        $counts = DB::table('answer_options')
            ->join('answers', 'answer_options.answer_id', '=', 'answers.id')
            ->join('responses', 'answers.response_id', '=', 'responses.id')
            ->where('answers.question_id', $question->id)
            ->whereNotNull('responses.submitted_at')
            ->select('answer_options.option_id', DB::raw('COUNT(*) as count'))
            ->groupBy('answer_options.option_id')
            ->pluck('count', 'option_id');

        // チェックボックスの回答者数（1人が複数選択しても1カウント）
        $answeredCount = DB::table('answers')
            ->join('responses', 'answers.response_id', '=', 'responses.id')
            ->where('answers.question_id', $question->id)
            ->whereNotNull('responses.submitted_at')
            ->count();

        $options = $question->options->map(function ($option) use ($counts, $answeredCount) {
            $count = $counts->get($option->id, 0);
            return [
                'id'         => $option->id,
                'label'      => $option->label,
                'count'      => $count,
                'percentage' => $answeredCount > 0 ? round($count / $answeredCount * 100, 1) : 0,
            ];
        });

        return [
            'question_id'    => $question->id,
            'label'          => $question->label,
            'type'           => $question->type,
            'answered_count' => $answeredCount,
            'total'          => $totalResponses,
            'options'        => $options->toArray(),
        ];
    }

    private function summarizeRating($question, int $totalResponses): array
    {
        $counts = DB::table('answers')
            ->join('responses', 'answers.response_id', '=', 'responses.id')
            ->where('answers.question_id', $question->id)
            ->whereNotNull('answers.value')
            ->whereNotNull('responses.submitted_at')
            ->select('answers.value', DB::raw('COUNT(*) as count'))
            ->groupBy('answers.value')
            ->pluck('count', 'value');

        $answeredCount = $counts->sum();
        $average = $answeredCount > 0
            ? round($counts->map(fn($c, $v) => $c * (int) $v)->sum() / $answeredCount, 2)
            : null;

        $min = $question->scale_min ?? 1;
        $max = $question->scale_max ?? 5;
        $options = [];
        for ($i = $min; $i <= $max; $i++) {
            $count = $counts->get((string) $i, 0);
            $options[] = [
                'label'      => (string) $i,
                'count'      => $count,
                'percentage' => $answeredCount > 0 ? round($count / $answeredCount * 100, 1) : 0,
            ];
        }

        return [
            'question_id'    => $question->id,
            'label'          => $question->label,
            'type'           => $question->type,
            'answered_count' => $answeredCount,
            'total'          => $totalResponses,
            'average'        => $average,
            'options'        => $options,
        ];
    }

    private function summarizeText($question): array
    {
        $values = DB::table('answers')
            ->join('responses', 'answers.response_id', '=', 'responses.id')
            ->where('answers.question_id', $question->id)
            ->whereNotNull('answers.value')
            ->whereNotNull('responses.submitted_at')
            ->orderByDesc('responses.submitted_at')
            ->pluck('answers.value');

        return [
            'question_id'    => $question->id,
            'label'          => $question->label,
            'type'           => $question->type,
            'answered_count' => $values->count(),
            'values'         => $values->toArray(),
        ];
    }
}
