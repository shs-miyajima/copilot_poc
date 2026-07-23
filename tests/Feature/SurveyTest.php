<?php

namespace Tests\Feature;

use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SurveyTest extends TestCase
{
    use RefreshDatabase;

    // ----------------------------------------------------------------
    // アンケート作成
    // ----------------------------------------------------------------

    public function test_admin_can_create_survey(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post(route('admin.surveys.store'), [
            'title'               => 'テストアンケート',
            'description'         => '説明文',
            'response_limit_type' => 'none',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('surveys', [
            'title'   => 'テストアンケート',
            'status'  => 'draft',
            'user_id' => $admin->id,
        ]);
    }

    public function test_survey_requires_title(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.surveys.store'), [
            'title'               => '',
            'response_limit_type' => 'none',
        ])->assertSessionHasErrors('title');
    }

    public function test_viewer_cannot_create_survey(): void
    {
        $viewer = User::factory()->viewer()->create();

        $this->actingAs($viewer)->post(route('admin.surveys.store'), [
            'title'               => '閲覧者のアンケート',
            'response_limit_type' => 'none',
        ])->assertForbidden();
    }

    // ----------------------------------------------------------------
    // アンケート更新・削除
    // ----------------------------------------------------------------

    public function test_admin_can_update_survey(): void
    {
        $admin  = User::factory()->admin()->create();
        $survey = Survey::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)->put(route('admin.surveys.update', $survey), [
            'title'               => '更新後タイトル',
            'response_limit_type' => 'none',
        ])->assertRedirect();

        $this->assertDatabaseHas('surveys', ['title' => '更新後タイトル']);
    }

    public function test_editor_cannot_update_another_users_survey(): void
    {
        $editor      = User::factory()->editor()->create();
        $otherAdmin  = User::factory()->admin()->create();
        $survey      = Survey::factory()->create(['user_id' => $otherAdmin->id]);

        $this->actingAs($editor)->put(route('admin.surveys.update', $survey), [
            'title'               => '不正更新',
            'response_limit_type' => 'none',
        ])->assertForbidden();
    }

    public function test_admin_can_soft_delete_survey(): void
    {
        $admin  = User::factory()->admin()->create();
        $survey = Survey::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)->delete(route('admin.surveys.destroy', $survey))
            ->assertRedirect(route('admin.surveys.index'));

        $this->assertSoftDeleted('surveys', ['id' => $survey->id]);
    }

    // ----------------------------------------------------------------
    // ステータス変更
    // ----------------------------------------------------------------

    public function test_cannot_publish_survey_without_questions(): void
    {
        $admin  = User::factory()->admin()->create();
        $survey = Survey::factory()->create(['user_id' => $admin->id, 'status' => 'draft']);

        $this->actingAs($admin)->patch(route('admin.surveys.updateStatus', $survey), [
            'status' => 'published',
        ])->assertSessionHasErrors();
    }

    public function test_can_publish_survey_with_questions(): void
    {
        $admin   = User::factory()->admin()->create();
        $survey  = Survey::factory()->create(['user_id' => $admin->id, 'status' => 'draft']);
        $survey->questions()->create([
            'type'        => 'text',
            'label'       => 'Q1',
            'is_required' => true,
            'order'       => 0,
        ]);

        $this->actingAs($admin)->patch(route('admin.surveys.updateStatus', $survey), [
            'status' => 'published',
        ])->assertRedirect();

        $this->assertDatabaseHas('surveys', ['id' => $survey->id, 'status' => 'published']);
    }

    // ----------------------------------------------------------------
    // 複製
    // ----------------------------------------------------------------

    public function test_admin_can_duplicate_survey(): void
    {
        $admin  = User::factory()->admin()->create();
        $survey = Survey::factory()->create(['user_id' => $admin->id, 'title' => 'オリジナル']);

        $this->actingAs($admin)->post(route('admin.surveys.duplicate', $survey))
            ->assertRedirect();

        $this->assertDatabaseHas('surveys', ['title' => 'オリジナル（コピー）', 'status' => 'draft']);
    }
}
