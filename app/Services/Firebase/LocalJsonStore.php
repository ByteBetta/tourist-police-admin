<?php

namespace App\Services\Firebase;

use Illuminate\Support\Facades\File;

class LocalJsonStore implements DocumentStore
{
    public function list(string $collection): array
    {
        $documents = $this->read()[$this->key($collection)] ?? [];

        return array_values(array_map(
            fn (array $document, string $id): array => $this->withKeys($id, $document),
            $documents,
            array_keys($documents),
        ));
    }

    public function get(string $collection, string $id): ?array
    {
        $document = $this->read()[$this->key($collection)][$id] ?? null;

        return $document ? $this->withKeys($id, $document) : null;
    }

    public function put(string $collection, string $id, array $data): array
    {
        $store = $this->read();
        $key = $this->key($collection);
        $store[$key] ??= [];
        $store[$key][$id] = $this->withoutKeys($data);
        $this->write($store);

        return $this->withKeys($id, $store[$key][$id]);
    }

    public function patch(string $collection, string $id, array $data): array
    {
        $existing = $this->get($collection, $id) ?? [];

        return $this->put($collection, $id, array_merge($existing, $this->withoutKeys($data)));
    }

    public function delete(string $collection, string $id): void
    {
        $store = $this->read();
        unset($store[$this->key($collection)][$id]);
        $this->write($store);
    }

    public function driver(): string
    {
        return 'local';
    }

    /**
     * @return array<string, array<string, array<string, mixed>>>
     */
    protected function read(): array
    {
        $path = $this->path();

        if (! is_file($path)) {
            return [];
        }

        /** @var array<string, array<string, array<string, mixed>>> $data */
        $data = json_decode((string) file_get_contents($path), true) ?: [];

        return $data;
    }

    /**
     * @param  array<string, mixed>  $store
     */
    protected function write(array $store): void
    {
        $path = $this->path();
        File::ensureDirectoryExists(dirname($path));
        file_put_contents($path, json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    protected function path(): string
    {
        return config('services.firebase.local_store', storage_path('app/firestore-local.json'));
    }

    protected function key(string $collection): string
    {
        return trim($collection, '/');
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    protected function withKeys(string $id, array $document): array
    {
        $document['id'] = $id;
        $document['__key'] = $id;

        return $document;
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    protected function withoutKeys(array $document): array
    {
        unset($document['id'], $document['__key']);

        return $document;
    }
}
