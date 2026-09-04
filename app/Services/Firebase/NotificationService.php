<?php

namespace App\Services\Firebase;

use App\Repositories\UserRepository;
use Illuminate\Support\Str;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Throwable;

class NotificationService
{
    public function __construct(
        protected DocumentStore $store,
        protected UserRepository $users,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function notifyUser(?string $uid, string $title, string $body, array $data = []): void
    {
        if (! $uid) {
            return;
        }

        $this->store->put('notifications', (string) Str::uuid(), [
            'userId' => $uid,
            'title' => $title,
            'body' => $body,
            'data' => $data,
            'read' => false,
            'createdAt' => now()->toIso8601String(),
        ]);

        $this->sendFcm($uid, $title, $body, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function sendFcm(string $uid, string $title, string $body, array $data): void
    {
        $messaging = $this->messaging();

        if (! $messaging) {
            return;
        }

        $token = data_get($this->users->find($uid), 'fcmToken');

        if (! $token) {
            return;
        }

        try {
            $message = CloudMessage::new()
                ->toToken($token)
                ->withNotification(FcmNotification::create($title, $body))
                ->withData(array_map('strval', $data));

            $messaging->send($message);
        } catch (Throwable) {
            // FCM is best-effort; Firestore still holds the in-app notification.
        }
    }

    protected function messaging(): ?Messaging
    {
        if (! is_file((string) config('services.firebase.credentials'))) {
            return null;
        }

        try {
            return app('firebase.messaging');
        } catch (Throwable) {
            return null;
        }
    }
}
