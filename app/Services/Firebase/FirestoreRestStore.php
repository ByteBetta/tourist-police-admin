<?php

namespace App\Services\Firebase;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class FirestoreRestStore implements DocumentStore
{
    public function list(string $collection): array
    {
        $response = $this->http()->get($this->collectionUrl($collection));

        if ($response->status() === 404) {
            return [];
        }

        $this->throwIfFailed($response->status(), $response->body());

        $documents = [];

        foreach ($response->json('documents') ?? [] as $document) {
            $documents[] = $this->hydrate($document);
        }

        return $documents;
    }

    public function get(string $collection, string $id): ?array
    {
        $response = $this->http()->get($this->documentUrl($collection, $id));

        if ($response->status() === 404) {
            return null;
        }

        $this->throwIfFailed($response->status(), $response->body());

        return $this->hydrate($response->json());
    }

    public function put(string $collection, string $id, array $data): array
    {
        $payload = ['fields' => FirestoreValueConverter::toFirestore($data)];

        $response = $this->http()->post($this->collectionUrl($collection).'?documentId='.urlencode($id), $payload);

        if (in_array($response->status(), [409, 400], true)) {
            return $this->patch($collection, $id, $data);
        }

        $this->throwIfFailed($response->status(), $response->body());

        return $this->hydrate($response->json()) ?: array_merge($data, ['id' => $id, '__key' => $id]);
    }

    public function patch(string $collection, string $id, array $data): array
    {
        $existing = $this->get($collection, $id) ?? [];
        $merged = array_merge($existing, $data, ['id' => $id]);

        $payload = ['fields' => FirestoreValueConverter::toFirestore($merged)];
        $mask = collect(array_keys($payload['fields']))
            ->map(fn (string $field): string => 'updateMask.fieldPaths='.urlencode($field))
            ->implode('&');

        $response = $this->http()->patch($this->documentUrl($collection, $id).($mask ? '?'.$mask : ''), $payload);

        $this->throwIfFailed($response->status(), $response->body());

        return $this->hydrate($response->json()) ?: array_merge($merged, ['__key' => $id]);
    }

    public function delete(string $collection, string $id): void
    {
        $response = $this->http()->delete($this->documentUrl($collection, $id));

        if ($response->status() === 404) {
            return;
        }

        $this->throwIfFailed($response->status(), $response->body());
    }

    public function driver(): string
    {
        return 'firestore';
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    protected function hydrate(array $document): array
    {
        $name = $document['name'] ?? '';
        $id = Str::afterLast($name, '/');
        $data = FirestoreValueConverter::fromFirestore($document['fields'] ?? []);
        $data['id'] = $id;
        $data['__key'] = $id;

        return $data;
    }

    protected function collectionUrl(string $collection): string
    {
        return $this->baseUrl().'/'.trim($collection, '/');
    }

    protected function documentUrl(string $collection, string $id): string
    {
        return $this->collectionUrl($collection).'/'.rawurlencode($id);
    }

    protected function baseUrl(): string
    {
        $projectId = config('services.firebase.project_id');

        if (! $projectId) {
            throw new RuntimeException('FIREBASE_PROJECT_ID is not configured.');
        }

        return 'https://firestore.googleapis.com/v1/projects/'.$projectId.'/databases/(default)/documents';
    }

    protected function http(): PendingRequest
    {
        return Http::withToken($this->accessToken())
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }

    protected function accessToken(): string
    {
        $path = config('services.firebase.credentials');

        if (! is_string($path) || ! is_file($path)) {
            throw new RuntimeException('Firebase service account JSON was not found.');
        }

        $credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/datastore', 'https://www.googleapis.com/auth/cloud-platform'],
            $path,
        );

        $token = $credentials->fetchAuthToken();

        return $token['access_token'] ?? throw new RuntimeException('Unable to fetch a Google access token.');
    }

    protected function throwIfFailed(int $status, string $body): void
    {
        if ($status >= 200 && $status < 300) {
            return;
        }

        throw new RuntimeException("Firestore request failed ({$status}): {$body}");
    }
}
