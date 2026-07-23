<?php

namespace App\Services;

use App\Exceptions\DuplicateResponseException;
use App\Models\Answer;
use App\Models\Response;
use App\Models\Survey;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ResponseService
{
    /**
     * 回答を保存する
     *
     * @param  Survey   $survey
     * @param  array    $answers  ['question_id' => value|array]
     * @param  Request  $request
     * @return Response
     * @throws DuplicateResponseException
     */
    public function store(Survey $survey, array $answers, Request $request): Response
    {
        $cookieToken = $request->cookie('survey_token_' . $survey->id);
        $ipHash      = hash('sha256', $request->ip());

        $this->checkDuplicate($survey, $cookieToken, $ipHash);

        // 新しい Cookie トークンを生成
        $newCookieToken = $cookieToken ?? Str::random(40);

        $response = Response::create([
            'survey_id'       => $survey->id,
            'respondent_token' => Str::uuid()->toString(),
            'ip_address'      => $survey->response_limit_type === 'ip'     ? $ipHash        : null,
            'cookie_token'    => $survey->response_limit_type === 'cookie' ? $newCookieToken : null,
            'submitted_at'    => now(),
        ]);

        $this->saveAnswers($response, $survey, $answers);

        return $response;
    }

    /**
     * 重複回答チェック
     *
     * @throws DuplicateResponseException
     */
    private function checkDuplicate(Survey $survey, ?string $cookieToken, string $ipHash): void
    {
        if ($survey->response_limit_type === 'none') {
            return;
        }

        if ($survey->response_limit_type === 'cookie' && $cookieToken) {
            $exists = Response::where('survey_id', $survey->id)
                ->where('cookie_token', $cookieToken)
                ->whereNotNull('submitted_at')
                ->exists();
            if ($exists) {
                throw new DuplicateResponseException();
            }
        }

        if ($survey->response_limit_type === 'ip') {
            $exists = Response::where('survey_id', $survey->id)
                ->where('ip_address', $ipHash)
                ->whereNotNull('submitted_at')
                ->exists();
            if ($exists) {
                throw new DuplicateResponseException();
            }
        }
    }

    /**
     * 回答データを answers / answer_options に一括保存する
     */
    private function saveAnswers(Response $response, Survey $survey, array $answers): void
    {
        // questions を id でインデックス化して N+1 を回避
        $questions = $survey->questions()->with('options')->get()->keyBy('id');

        foreach ($answers as $questionId => $value) {
            $question = $questions->get($questionId);
            if (! $question) {
                continue;
            }

            if ($question->type === 'checkbox') {
                // 複数選択: value は選択肢IDの配列
                $answer = Answer::create([
                    'response_id' => $response->id,
                    'question_id' => $questionId,
                    'value'       => null,
                ]);

                $selectedIds = is_array($value) ? $value : [];
                foreach ($selectedIds as $optionId) {
                    $answer->options()->create(['option_id' => $optionId]);
                }
            } elseif (in_array($question->type, ['radio', 'dropdown'], true)) {
                // 単一選択: value は選択肢ID
                $answer = Answer::create([
                    'response_id' => $response->id,
                    'question_id' => $questionId,
                    'value'       => $value,
                ]);
                if ($value) {
                    $answer->options()->create(['option_id' => $value]);
                }
            } else {
                // テキスト・数値・日付・評価スケール
                Answer::create([
                    'response_id' => $response->id,
                    'question_id' => $questionId,
                    'value'       => $value,
                ]);
            }
        }
    }

    /**
     * Cookie のトークン文字列を返す（Controller でセットする）
     */
    public function getCookieToken(Survey $survey, Request $request): string
    {
        return $request->cookie('survey_token_' . $survey->id) ?? Str::random(40);
    }
}
