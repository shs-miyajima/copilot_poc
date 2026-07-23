<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use App\Models\Response;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultExportTest extends TestCase
{
    use RefreshDatabase;

    private function createSurveyWithResponses(): array
    {
        $admin    = User::factory()->admin()->create();
        $survey   = Survey::factory()->create(['user_id' => $admin->id, 'status' => 'published']);
        $question = $survey->questions()->create([
            'type'        => 'text',
            'label'       => '感想',
            'is_required' => false,
            'order'       => 0,
        ]);

        $response = $survey->responses()->create(['submitted_at' => now()]);
        $response->answers()->create(['question_id' => $question->id, 'value' => 'とても良かった']);

        return compact('admin', 'survey', 'question', 'response');
    }

    // ----------------------------------------------------------------
    // 集計表示
    // ----------------------------------------------------------------

    public function test_admin_can_view_results(): void
    {
        ['admin' => $admin, 'survey' => $survey] = $this->createSurveyWithResponses();

        $this->actingAs($admin)
            ->get(route('admin.surveys.results.index', $survey))
            ->assertOk()
            ->assertSee($survey->title)
            ->assertSee('感想');
    }

    public function test_viewer_can_view_results(): void
    {
        ['survey' => $survey] = $this->createSurveyWithResponses();
        $viewer = User::factory()->viewer()->create();

        $this->actingAs($viewer)
            ->get(route('admin.surveys.results.index', $survey))
            ->assertOk();
    }

    // ----------------------------------------------------------------
    // CSV エクスポート
    // ----------------------------------------------------------------

    public function test_admin_can_export_csv(): void
    {
        ['admin' => $admin, 'survey' => $survey] = $this->createSurveyWithResponses();

        $response = $this->actingAs($admin)
            ->get(route('admin.surveys.results.export.csv', $survey));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('感想', $content);
        $this->assertStringContainsString('とても良かった', $content);
    }

    public function test_csv_contains_required_columns(): void
    {
        ['admin' => $admin, 'survey' => $survey] = $this->createSurveyWithResponses();

        $content = $this->actingAs($admin)
            ->get(route('admin.surveys.results.export.csv', $survey))
            ->streamedContent();

        // ヘッダー行のチェック
        $this->assertStringContainsString('回答ID', $content);
        $this->assertStringContainsString('回答日時', $content);
    }

    // ----------------------------------------------------------------
    // 回答個別削除
    // ----------------------------------------------------------------

    public function test_admin_can_delete_response(): void
    {
        ['admin' => $admin, 'survey' => $survey, 'response' => $response] = $this->createSurveyWithResponses();

        $this->actingAs($admin)
            ->delete(route('admin.surveys.results.destroy', [$survey, $response]))
            ->assertRedirect();

        $this->assertDatabaseMissing('responses', ['id' => $response->id]);
    }

    public function test_viewer_cannot_delete_response(): void
    {
        ['survey' => $survey, 'response' => $response] = $this->createSurveyWithResponses();
        $viewer = User::factory()->viewer()->create();

        $this->actingAs($viewer)
            ->delete(route('admin.surveys.results.destroy', [$survey, $response]))
            ->assertForbidden();
    }

    public function test_cannot_delete_response_from_another_survey(): void
    {
        $admin     = User::factory()->admin()->create();
        $surveyA   = Survey::factory()->create(['user_id' => $admin->id]);
        $surveyB   = Survey::factory()->create(['user_id' => $admin->id]);
        $responseB = $surveyB->responses()->create(['submitted_at' => now()]);

        // surveyA のルートで surveyB の回答を削除しようとする
        $this->actingAs($admin)
            ->delete(route('admin.surveys.results.destroy', [$surveyA, $responseB]))
            ->assertNotFound();
    }
}
