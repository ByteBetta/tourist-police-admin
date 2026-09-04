<?php

namespace App\Repositories;

use App\Enums\ReportSource;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\User;
use App\Services\Firebase\DocumentStore;
use Illuminate\Support\Str;

class SmsReportRepository
{
    public function __construct(
        protected DocumentStore $store,
        protected ReportRepository $reports,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function all(?string $search = null): array
    {
        $items = collect($this->store->list('sms_reports'))
            ->map(fn (array $item): array => $this->normalize($item));

        if ($search) {
            $needle = Str::lower($search);
            $items = $items->filter(function (array $item) use ($needle): bool {
                return str_contains(Str::lower(($item['from'] ?? '').' '.($item['body'] ?? '')), $needle);
            });
        }

        return $items
            ->sortByDesc(fn (array $item): string => (string) ($item['createdAt'] ?? ''))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $id): ?array
    {
        $item = $this->store->get('sms_reports', $id);

        return $item ? $this->normalize($item) : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function convertToReport(string $id, User $actor): array
    {
        $sms = $this->find($id) ?? throw new \RuntimeException('SMS report not found.');

        if (! empty($sms['reportId'])) {
            return $this->reports->find($sms['reportId']) ?? throw new \RuntimeException('Linked report is missing.');
        }

        $report = $this->reports->create([
            'title' => 'SMS assistance request',
            'description' => $sms['body'] ?? 'Emergency SMS received without additional details.',
            'type' => ReportType::Emergency->value,
            'status' => ReportStatus::Pending->value,
            'source' => ReportSource::Sms->value,
            'touristName' => $sms['from'] ?? 'Unknown sender',
            'touristPhone' => $sms['from'] ?? null,
            'smsBody' => $sms['body'] ?? null,
            'location' => $sms['location'] ?? null,
        ]);

        $this->reports->addUpdate($report['id'], [
            'status' => ReportStatus::Pending->value,
            'note' => 'Converted from emergency SMS inbox.',
            'actorId' => $actor->firebaseUid(),
            'actorName' => $actor->name,
            'actorRole' => $actor->role?->value,
        ]);

        $this->store->patch('sms_reports', $id, [
            'reportId' => $report['id'],
            'convertedAt' => now()->toIso8601String(),
            'convertedBy' => $actor->firebaseUid(),
        ]);

        return $report;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected function normalize(array $item): array
    {
        $id = (string) ($item['id'] ?? $item['__key'] ?? Str::uuid());

        return [
            '__key' => $id,
            'id' => $id,
            'from' => $item['from'] ?? 'Unknown',
            'body' => $item['body'] ?? '',
            'location' => is_array($item['location'] ?? null) ? $item['location'] : null,
            'reportId' => $item['reportId'] ?? null,
            'convertedAt' => $item['convertedAt'] ?? null,
            'createdAt' => $item['createdAt'] ?? now()->toIso8601String(),
        ];
    }
}
