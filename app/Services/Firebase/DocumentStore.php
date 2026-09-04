<?php

namespace App\Services\Firebase;

interface DocumentStore
{
    /**
     * @return list<array<string, mixed>>
     */
    public function list(string $collection): array;

    /**
     * @return array<string, mixed>|null
     */
    public function get(string $collection, string $id): ?array;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function put(string $collection, string $id, array $data): array;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function patch(string $collection, string $id, array $data): array;

    public function delete(string $collection, string $id): void;

    public function driver(): string;
}
