<?php

namespace App\Repositories;

use App\Enums\ReportSource;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\User;
use App\Services\Firebase\DocumentStore;
use Illuminate\Support\Str;

class ReportRepository
{
    public function __construct(protected DocumentStore $store) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return list<array<string, mixed>>
     */
    public function all(array $filters = [], ?User $viewer = null): array
    {
        $reports = collect($this->store->list('reports'))
            ->map(fn (array $report): array => $this->normalize($report));

        if ($viewer?->isResponder()) {
            $uid = $viewer->firebaseUid();
            $reports = $reports->filter(function (array $report) use ($uid): bool {
                $assignedTo = $report['assignedTo'] ?? null;
                $status = $report['status'] ?? ReportStatus::Pending->value;

                return $assignedTo === $uid
                    || ($assignedTo === null && in_array($status, [ReportStatus::Approved->value, ReportStatus::Pending->value], true));
            });
        }

        if ($status = $filters['status'] ?? null) {
            $reports = $reports->where('status', $status);
        }

        if ($type = $filters['type'] ?? null) {
            $reports = $reports->where('type', $type);
        }

        if ($source = $filters['source'] ?? null) {
            $reports = $reports->where('source', $source);
        }

        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $needle = Str::lower($search);
            $reports = $reports->filter(function (array $report) use ($needle): bool {
                $haystack = Str::lower(implode(' ', [
                    $report['title'] ?? '',
                    $report['description'] ?? '',
                    $report['touristName'] ?? '',
                    $report['id'] ?? '',
                ]));

                return str_contains($haystack, $needle);
            });
        }

        return $reports
            ->sortByDesc(fn (array $report): string => (string) ($report['createdAt'] ?? ''))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $id): ?array
    {
        $report = $this->store->get('reports', $id);

        return $report ? $this->normalize($report) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $id = $data['id'] ?? (string) Str::uuid();
        $payload = $this->normalize(array_merge([
            'status' => ReportStatus::Pending->value,
            'source' => ReportSource::App->value,
            'type' => ReportType::Assistance->value,
            'photos' => [],
            'createdAt' => now()->toIso8601String(),
        ], $data, [
            'updatedAt' => now()->toIso8601String(),
        ]));

        return $this->store->put('reports', $id, $payload);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(string $id, array $data, ?User $actor = null, ?string $note = null): array
    {
        $payload = array_merge($data, ['updatedAt' => now()->toIso8601String()]);
        $report = $this->store->patch('reports', $id, $payload);

        $this->addUpdate($id, [
            'status' => $payload['status'] ?? ($report['status'] ?? null),
            'note' => $note,
            'actorId' => $actor?->firebaseUid(),
            'actorName' => $actor?->name,
            'actorRole' => $actor?->role?->value,
        ]);

        return $this->normalize($report);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function addUpdate(string $reportId, array $data): array
    {
        $id = (string) Str::uuid();

        return $this->store->put("reports/{$reportId}/updates", $id, array_merge($data, [
            'createdAt' => now()->toIso8601String(),
        ]));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function updates(string $reportId): array
    {
        return collect($this->store->list("reports/{$reportId}/updates"))
            ->sortByDesc(fn (array $update): string => (string) ($update['createdAt'] ?? ''))
            ->values()
            ->all();
    }

    /**
     * @return array<string, int>
     */
    public function stats(?User $viewer = null): array
    {
        $reports = $this->all(viewer: $viewer);
        $today = now()->toDateString();

        return [
            'pending' => collect($reports)->where('status', ReportStatus::Pending->value)->count(),
            'in_progress' => collect($reports)->whereIn('status', [
                ReportStatus::Assigned->value,
                ReportStatus::Accepted->value,
                ReportStatus::InProgress->value,
            ])->count(),
            'resolved_today' => collect($reports)
                ->where('status', ReportStatus::Resolved->value)
                ->filter(fn (array $report): bool => str_starts_with((string) ($report['updatedAt'] ?? ''), $today))
                ->count(),
            'sms' => collect($reports)->where('source', ReportSource::Sms->value)->count(),
            'app' => collect($reports)->where('source', ReportSource::App->value)->count(),
            'total' => count($reports),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalize(array $report): array
    {
        $id = (string) ($report['id'] ?? $report['__key'] ?? Str::uuid());

        return [
            '__key' => $id,
            'id' => $id,
            'title' => $report['title'] ?? 'Untitled report',
            'description' => $report['description'] ?? '',
            'type' => $report['type'] ?? ReportType::Assistance->value,
            'status' => $report['status'] ?? ReportStatus::Pending->value,
            'source' => $report['source'] ?? ReportSource::App->value,
            'touristId' => $report['touristId'] ?? null,
            'touristName' => $report['touristName'] ?? 'Unknown tourist',
            'touristPhone' => $report['touristPhone'] ?? null,
            'language' => $report['language'] ?? 'en',
            'photos' => array_values($report['photos'] ?? []),
            'location' => is_array($report['location'] ?? null) ? $report['location'] : null,
            'assignedTo' => $report['assignedTo'] ?? null,
            'assignedToName' => $report['assignedToName'] ?? null,
            'assignedBy' => $report['assignedBy'] ?? null,
            'assignedAt' => $report['assignedAt'] ?? null,
            'smsBody' => $report['smsBody'] ?? null,
            'createdAt' => $report['createdAt'] ?? now()->toIso8601String(),
            'updatedAt' => $report['updatedAt'] ?? ($report['createdAt'] ?? now()->toIso8601String()),
        ];
    }
}
