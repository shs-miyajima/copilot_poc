<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResponseTest extends TestCase
{
    use RefreshDatabase;

    private function createPublishedSurvey(array $surveyAttributes = []): Survey
    {
        $admin  = User::factory()->admin()->create();
        $survey = Survey::factory()->create(array_merge([
            'user_id' => $admin->id,
            'status'  => 'published',
        ], $surveyAttributes));

        return $survey;
    }

    private function addTextQuestion(Survey $survey, bool $required = true): Question
    {
        return $survey->questions()->create([
            'type'        => 'text',
            'label'       => 'お名前を入力してください',
            'is_required' => $required,
            'order'       => 0,
        ]);
    }

    // ----------------------------------------------------------------
    // 回答フォーム表示
    // ----------------------------------------------------------------

    public function test_public_can_view_published_survey(): void
    {
        $survey = $this->createPublishedSurvey();

        $this->get(route('survey.show', $survey->public_token))
            ->assertOk()
            ->assertSee($survey->title);
    }

    public function test_closed_survey_redirects_to_closed_page(): void
    {
        $survey = $this->createPublishedSurvey(['status' => 'closed']);

        $this->get(route('survey.show', $survey->public_token))
            ->assertRedirect(route('survey.closed', $survey->public_token));
    }

    public function test_draft_survey_redirects_to_closed_page(): void
    {
        $survey = $this->createPublishedSurvey(['status' => 'draft']);

        $this->get(route('survey.show', $survey->public_token))
            ->assertRedirect(route('survey.closed', $survey->public_token));
    }

    // ----------------------------------------------------------------
    // 回答送信・保存
    // ----------------------------------------------------------------

    public function test_can_submit_answer(): void
    {
        $survey   = $this->createPublishedSurvey();
        $question = $this->addTextQuestion($survey);

        $this->post(route('survey.submit', $survey->public_token), [
            'answers' => [$question->id => '山田太郎'],
        ])->assertRedirect(route('survey.thanks', $survey->public_token));

        $this->assertDatabaseHas('responses', [
            'survey_id'    => $survey->id,
        ]);
        $this->assertDatabaseHas('answers', [
            'question_id' => $question->id,
            'value'       => '山田太郎',
        ]);
    }

    public function test_required_question_blocks_empty_submission(): void
    {
        $survey   = $this->createPublishedSurvey();
        $question = $this->addTextQuestion($survey, required: true);

        $this->post(route('survey.submit', $survey->public_token), [
            'answers' => [$question->id => ''],
        ])->assertSessionHasErrors("answers.{$question->id}");
    }

    public function test_thanks_page_shows_after_submit(): void
    {
        $survey = $this->createPublishedSurvey(['thanks_message' => 'ありがとうございました！']);

        $this->get(route('survey.thanks', $survey->public_token))
            ->assertOk()
            ->assertSee('ありがとうございました！');
    }

    // ----------------------------------------------------------------
    // 受付終了チェック
    // ----------------------------------------------------------------

    public function test_survey_past_end_date_redirects_to_closed(): void
    {
        $survey = $this->createPublishedSurvey([
            'ends_at' => now()->subDay(),
        ]);

        $this->get(route('survey.show', $survey->public_token))
            ->assertRedirect(route('survey.closed', $survey->public_token));
    }

    public function test_survey_at_max_responses_redirects_to_closed(): void
    {
        $survey   = $this->createPublishedSurvey(['max_responses' => 1]);
        $question = $this->addTextQuestion($survey);

        // 上限まで回答を作成
        $survey->responses()->create(['submitted_at' => now()]);

        $this->get(route('survey.show', $survey->public_token))
            ->assertRedirect(route('survey.closed', $survey->public_token));
    }

    // ----------------------------------------------------------------
    // 重複回答チェック
    // ----------------------------------------------------------------

    public function test_cookie_based_duplicate_is_rejected(): void
    {
        $survey   = $this->createPublishedSurvey(['response_limit_type' => 'cookie']);
        $question = $this->addTextQuestion($survey);
        $token    = 'test-cookie-token-123';

        // 1回目の回答
        $survey->responses()->create([
            'cookie_token' => $token,
            'submitted_at' => now(),
        ]);

        // 2回目（同一Cookie）
        $this->withCookie('survey_token_' . $survey->id, $token)
            ->post(route('survey.submit', $survey->public_token), [
                'answers' => [$question->id => '重複テスト'],
            ])->assertSessionHasErrors('duplicate');
    }

    public function test_ip_based_duplicate_is_rejected(): void
    {
        $survey   = $this->createPublishedSurvey(['response_limit_type' => 'ip']);
        $question = $this->addTextQuestion($survey);
        $ipHash   = hash('sha256', '127.0.0.1');

        // 1回目の回答を直接作成
        $survey->responses()->create([
            'ip_address'   => $ipHash,
            'submitted_at' => now(),
        ]);

        // 2回目（同一IP: Laravel テストのデフォルトは 127.0.0.1）
        $this->post(route('survey.submit', $survey->public_token), [
            'answers' => [$question->id => '重複テスト'],
        ])->assertSessionHasErrors('duplicate');
    }
}
