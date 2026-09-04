<?php

namespace App\Services;

use App\Enums\ReportStatus;
use App\Models\User;
use App\Repositories\ReportRepository;
use App\Repositories\UserRepository;
use App\Services\Firebase\NotificationService;
use RuntimeException;

class ReportWorkflowService
{
    public function __construct(
        protected ReportRepository $reports,
        protected UserRepository $users,
        protected NotificationService $notifications,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function approve(string $id, User $actor, ?string $note = null): array
    {
        $this->assertAdmin($actor);

        $report = $this->updateStatus($id, ReportStatus::Approved, $actor, $note ?: 'Report approved for assignment.');
        $this->notifyTourist($report, 'Report approved', 'The Tourist Police approved your report and will assign a responder.');

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    public function reject(string $id, User $actor, string $note): array
    {
        $this->assertAdmin($actor);

        $report = $this->updateStatus($id, ReportStatus::Rejected, $actor, $note);
        $this->notifyTourist($report, 'Report rejected', $note);

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    public function assign(string $id, string $responderId, User $actor, ?string $note = null): array
    {
        $this->assertAdmin($actor);

        $responder = $this->users->find($responderId) ?? throw new RuntimeException('Responder not found.');

        $report = $this->reports->update($id, [
            'status' => ReportStatus::Assigned->value,
            'assignedTo' => $responder['id'],
            'assignedToName' => $responder['name'],
            'assignedBy' => $actor->firebaseUid(),
            'assignedAt' => now()->toIso8601String(),
        ], $actor, $note ?: "Assigned to {$responder['name']}.");

        $this->notifications->notifyUser(
            $responder['id'],
            'New case assigned',
            $report['title'] ?? 'A tourist report was assigned to you.',
            ['reportId' => $id],
        );
        $this->notifyTourist($report, 'Responder assigned', 'A Tourist Police responder was assigned to your report.');

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    public function accept(string $id, User $actor, ?string $note = null): array
    {
        $report = $this->reports->update($id, [
            'status' => ReportStatus::Accepted->value,
            'assignedTo' => $actor->firebaseUid(),
            'assignedToName' => $actor->name,
            'assignedAt' => now()->toIso8601String(),
        ], $actor, $note ?: 'Responder accepted the case.');

        $this->notifyTourist($report, 'Responder accepted', 'A responder accepted your request and is preparing to assist.');

        return $report;
    }

    /**
     * @return array<string, mixed>
     */
    public function updateStatus(string $id, ReportStatus $status, User $actor, ?string $note = null): array
    {
        $report = $this->reports->update($id, [
            'status' => $status->value,
        ], $actor, $note ?: "Status updated to {$status->label()}.");

        $this->notifyTourist(
            $report,
            'Report update',
            $note ?: "Your report is now {$status->label()}.",
        );

        return $report;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    protected function notifyTourist(array $report, string $title, string $body): void
    {
        $this->notifications->notifyUser($report['touristId'] ?? null, $title, $body, [
            'reportId' => $report['id'] ?? '',
            'status' => $report['status'] ?? '',
        ]);
    }

    protected function assertAdmin(User $actor): void
    {
        if (! $actor->isAdmin()) {
            throw new RuntimeException('Only administrators can perform this action.');
        }
    }
}
