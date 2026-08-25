<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class TenantManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_user_can_manage_companies_and_switch_context(): void
    {
        $superUser = User::create([
            'name' => 'Super Admin',
            'email' => 'super@example.com',
            'password' => 'password',
            'role' => 'super_admin',
        ]);

        $response = $this->post('/tenant/login', [
            'email' => $superUser->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/tenant/dashboard');

        $this->get('/tenant/companies')
            ->assertOk()
            ->assertSee('企業一覧');

        $this->post('/tenant/companies', ['name' => 'Acme Corp'])
            ->assertRedirect('/tenant/companies');

        $this->assertDatabaseHas('companies', ['name' => 'Acme Corp']);

        $company = Company::where('name', 'Acme Corp')->firstOrFail();

        $this->post("/tenant/companies/{$company->id}/switch")
            ->assertRedirect('/tenant/dashboard');

        $this->get('/tenant/dashboard')
            ->assertOk()
            ->assertSee('Acme Corp');
    }

    public function test_admin_can_create_users_individually_and_by_csv(): void
    {
        $company = Company::create(['name' => 'Contoso']);
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => 'password',
            'role' => 'admin',
            'company_id' => $company->id,
        ]);

        $this->post('/tenant/login', [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect('/tenant/dashboard');

        $this->post("/tenant/companies/{$company->id}/users", [
            'name' => 'Alice Example',
            'email' => 'alice@example.com',
            'password' => 'password',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'alice@example.com',
            'company_id' => $company->id,
            'role' => 'user',
        ]);

        $csv = UploadedFile::fake()->createWithContent('users.csv', "name,email\nBob Example,bob@example.com\n");

        $this->post("/tenant/companies/{$company->id}/users/import", [
            'csv' => $csv,
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'bob@example.com',
            'company_id' => $company->id,
            'role' => 'user',
        ]);
    }
}
