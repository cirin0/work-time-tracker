<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $employee;
    protected User $manager;
    protected User $admin;

    public function test_user_can_view_their_own_audit_logs(): void
    {
        AuditLog::factory()->count(5)->create(['user_id' => $this->employee->id]);
        AuditLog::factory()->count(3)->create(['user_id' => $this->manager->id]);

        $response = $this->actingAs($this->employee, 'api')
            ->getJson('/api/audit-logs');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_manager_can_view_company_audit_logs(): void
    {
        AuditLog::factory()->count(5)->create(['user_id' => $this->employee->id]);

        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);
        AuditLog::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->manager, 'api')
            ->getJson('/api/audit-logs/all');

        $response->assertOk();
        $data = $response->json('data');

        // Should only see logs from their company
        $this->assertGreaterThanOrEqual(5, count($data));
        foreach ($data as $log) {
            if (isset($log['user']['id'])) {
                $user = User::find($log['user']['id']);
                $this->assertEquals($this->company->id, $user->company_id);
            }
        }
    }

    public function test_admin_can_view_all_audit_logs(): void
    {
        AuditLog::factory()->count(5)->create(['user_id' => $this->employee->id]);

        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);
        AuditLog::factory()->count(3)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->admin, 'api')
            ->getJson('/api/audit-logs/all');

        $response->assertOk();
        $data = $response->json('data');

        // Admin should see all logs
        $this->assertGreaterThanOrEqual(8, count($data));
    }

    public function test_employee_cannot_view_all_audit_logs(): void
    {
        $response = $this->actingAs($this->employee, 'api')
            ->getJson('/api/audit-logs/all');

        $response->assertForbidden();
    }

    public function test_audit_log_includes_user_information(): void
    {
        AuditLog::factory()->create([
            'user_id' => $this->employee->id,
            'action' => 'login',
        ]);

        $response = $this->actingAs($this->employee, 'api')
            ->getJson('/api/audit-logs');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user' => [
                            'id',
                            'name',
                            'email',
                        ],
                        'action',
                        'model_type',
                        'model_name',
                        'model_id',
                        'old_values',
                        'new_values',
                        'ip_address',
                        'user_agent',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);
    }

    public function test_audit_log_pagination_works(): void
    {
        AuditLog::factory()->count(60)->create(['user_id' => $this->employee->id]);

        $response = $this->actingAs($this->employee, 'api')
            ->getJson('/api/audit-logs?per_page=20');

        $response->assertOk();
        $this->assertCount(20, $response->json('data'));
    }

    public function test_unauthenticated_user_cannot_access_audit_logs(): void
    {
        $response = $this->getJson('/api/audit-logs');
        $response->assertUnauthorized();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->employee = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => UserRole::EMPLOYEE,
        ]);
        $this->manager = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => UserRole::MANAGER,
        ]);
        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => UserRole::ADMIN,
        ]);
    }
}
