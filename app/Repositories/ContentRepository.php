<?php

namespace App\Repositories;

use App\Services\Firebase\DocumentStore;
use Illuminate\Support\Str;

class ContentRepository
{
    public function __construct(protected DocumentStore $store) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function all(string $collection, ?string $search = null): array
    {
        $items = collect($this->store->list($collection))
            ->map(fn (array $item): array => $this->normalize($item));

        if ($search) {
            $needle = Str::lower($search);
            $items = $items->filter(function (array $item) use ($needle): bool {
                return str_contains(Str::lower(implode(' ', [
                    $item['name'] ?? '',
                    $item['title'] ?? '',
                    $item['description'] ?? '',
                    $item['address'] ?? '',
                    $item['phone'] ?? '',
                ])), $needle);
            });
        }

        return $items
            ->sortBy(fn (array $item): string => Str::lower((string) ($item['name'] ?? $item['title'] ?? $item['id'])))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $collection, string $id): ?array
    {
        $item = $this->store->get($collection, $id);

        return $item ? $this->normalize($item) : null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function save(string $collection, array $data, ?string $id = null): array
    {
        $id ??= (string) Str::uuid();
        $payload = array_merge($data, [
            'updatedAt' => now()->toIso8601String(),
            'createdAt' => $data['createdAt'] ?? now()->toIso8601String(),
        ]);

        return $this->normalize($this->store->put($collection, $id, $payload));
    }

    public function delete(string $collection, string $id): void
    {
        $this->store->delete($collection, $id);
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected function normalize(array $item): array
    {
        $id = (string) ($item['id'] ?? $item['__key'] ?? Str::uuid());
        $item['id'] = $id;
        $item['__key'] = $id;

        return $item;
    }
}
