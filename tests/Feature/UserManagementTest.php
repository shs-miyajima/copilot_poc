<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    // ----------------------------------------------------------------
    // ログイン
    // ----------------------------------------------------------------

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->admin()->create([
            'email'    => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->post(route('login.post'), [
            'email'    => 'admin@test.com',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'user@test.com', 'password' => bcrypt('correct')]);

        $this->post(route('login.post'), [
            'email'    => 'user@test.com',
            'password' => 'wrong',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_access_admin(): void
    {
        $user = User::factory()->admin()->create(['is_active' => false]);

        // セッションに直接ログインしてからアクセス
        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    // ----------------------------------------------------------------
    // ユーザー一覧・作成
    // ----------------------------------------------------------------

    public function test_admin_can_view_user_list(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(3)->create();

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertViewIs('admin.users.index');
    }

    public function test_non_admin_cannot_view_user_list(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name'                  => '新規ユーザー',
            'email'                 => 'newuser@example.com',
            'password'              => 'Password1',
            'password_confirmation' => 'Password1',
            'role'                  => 'editor',
            'is_active'             => true,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'newuser@example.com',
            'role'  => 'editor',
        ]);
    }

    public function test_create_user_requires_strong_password(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name'                  => 'テスト',
            'email'                 => 'test2@example.com',
            'password'              => 'weak',     // 短すぎる・数字なし
            'password_confirmation' => 'weak',
            'role'                  => 'viewer',
        ])->assertSessionHasErrors('password');
    }

    public function test_create_user_requires_unique_email(): void
    {
        $admin    = User::factory()->admin()->create();
        $existing = User::factory()->create(['email' => 'dup@example.com']);

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name'                  => '重複',
            'email'                 => 'dup@example.com',
            'password'              => 'Password1',
            'password_confirmation' => 'Password1',
            'role'                  => 'viewer',
        ])->assertSessionHasErrors('email');
    }

    // ----------------------------------------------------------------
    // ユーザー更新
    // ----------------------------------------------------------------

    public function test_admin_can_update_user(): void
    {
        $admin  = User::factory()->admin()->create();
        $target = User::factory()->viewer()->create();

        $this->actingAs($admin)->put(route('admin.users.update', $target), [
            'name'      => '更新後の名前',
            'email'     => $target->email,
            'role'      => 'editor',
            'is_active' => true,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'id'   => $target->id,
            'role' => 'editor',
            'name' => '更新後の名前',
        ]);
    }

    // ----------------------------------------------------------------
    // 有効/無効切り替え
    // ----------------------------------------------------------------

    public function test_admin_can_toggle_user_active_status(): void
    {
        $admin  = User::factory()->admin()->create();
        $target = User::factory()->create(['is_active' => true]);

        $this->actingAs($admin)
            ->patch(route('admin.users.toggleActive', $target))
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => false]);
    }

    public function test_admin_cannot_deactivate_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.users.toggleActive', $admin))
            ->assertForbidden();
    }

    // ----------------------------------------------------------------
    // ロール制限
    // ----------------------------------------------------------------

    public function test_viewer_redirected_from_survey_create(): void
    {
        $viewer = User::factory()->viewer()->create();

        $this->actingAs($viewer)
            ->get(route('admin.surveys.create'))
            ->assertForbidden();
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }
}
