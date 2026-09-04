<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Models\User;
use App\Repositories\ContentRepository;
use App\Repositories\ReportRepository;
use App\Services\ReportWorkflowService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.firebase.credentials' => null,
            'services.firebase.project_id' => null,
            'services.firebase.local_store' => storage_path('framework/testing/firestore-local.json'),
        ]);

        @unlink(config('services.firebase.local_store'));

        $this->seed(DatabaseSeeder::class);
    }

    protected function tearDown(): void
    {
        @unlink(config('services.firebase.local_store'));

        parent::tearDown();
    }

    protected function admin(): User
    {
        return User::query()->where('email', 'admin@touristpolice.ppc')->firstOrFail();
    }

    protected function responder(): User
    {
        return User::query()->where('email', 'responder@touristpolice.ppc')->firstOrFail();
    }

    public function test_guests_are_redirected_from_the_admin_panel(): void
    {
        $this->get('/admin')->assertRedirect();
    }

    public function test_administrator_can_open_operations_pages(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin')->assertOk();
        $this->get('/admin/reports')->assertOk();
        $this->get('/admin/reports/report-pending')->assertOk();
        $this->get('/admin/users')->assertOk();
        $this->get('/admin/sms-inbox')->assertOk();
        $this->get('/admin/attractions')->assertOk();
        $this->get('/admin/activities')->assertOk();
        $this->get('/admin/events')->assertOk();
        $this->get('/admin/emergency-contacts')->assertOk();
    }

    public function test_responder_cannot_open_admin_only_pages(): void
    {
        $this->actingAs($this->responder());

        $this->get('/admin')->assertOk();
        $this->get('/admin/reports')->assertOk();
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/sms-inbox')->assertForbidden();
        $this->get('/admin/attractions')->assertForbidden();
    }

    public function test_report_workflow_approves_assigns_and_accepts(): void
    {
        $workflow = app(ReportWorkflowService::class);

        $approved = $workflow->approve('report-pending', $this->admin());
        $this->assertSame(ReportStatus::Approved->value, $approved['status']);

        $assigned = $workflow->assign('report-pending', 'responder-uid', $this->admin());
        $this->assertSame(ReportStatus::Assigned->value, $assigned['status']);
        $this->assertSame('responder-uid', $assigned['assignedTo']);

        $accepted = $workflow->accept('report-pending', $this->responder());
        $this->assertSame(ReportStatus::Accepted->value, $accepted['status']);

        $report = app(ReportRepository::class)->find('report-pending');
        $this->assertSame(ReportStatus::Accepted->value, $report['status']);
    }

    public function test_offline_library_can_create_an_attraction(): void
    {
        $saved = app(ContentRepository::class)->save('attractions', [
            'name' => 'Baywalk',
            'address' => 'Puerto Princesa Baywalk',
            'description' => 'Waterfront promenade',
        ]);

        $this->assertSame('Baywalk', $saved['name']);
        $this->assertSame(
            'Baywalk',
            app(ContentRepository::class)->find('attractions', $saved['id'])['name'],
        );
    }
}
