# PPC Tourist Police Admin

Laravel 12 + Filament 5 web dashboard for **A Mobile-Based Tourist Assistance and Concern Reporting System** (Tourist Police Office of Puerto Princesa City).

The Flutter mobile app stays on Firebase. This panel reads and writes the same Cloud Firestore collections when a service account is configured. Without Firebase credentials it runs against a local JSON store so the dashboard can be demoed immediately.

## Features

- Firebase Auth login for `admin` and `responder` roles (local login in demo mode)
- Operations dashboard and report workflow: receive, approve, assign, accept, update, resolve
- Photo and map view for submitted reports
- Staff/tourist account management
- Offline-library CMS: attractions, activities, events, emergency contacts
- Emergency SMS inbox that converts messages into reports
- FCM / in-app notification documents on status changes

## Demo accounts (local mode)

| Role | Email | Password |
| --- | --- | --- |
| Administrator | `admin@touristpolice.ppc` | `password` |
| Responder | `responder@touristpolice.ppc` | `password` |

## Setup

PHP 8.2+ and Composer are required. On this machine a portable PHP 8.5 lives in `../.tools/php84`.

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Open `/admin`.

## Firebase

1. Place the Firebase service account JSON at `storage/app/firebase-credentials.json` (gitignored).
2. Set these in `.env`:

```
FIREBASE_PROJECT_ID=your-project-id
FIREBASE_CREDENTIALS=storage/app/firebase-credentials.json
FIREBASE_API_KEY=your-web-api-key
GOOGLE_MAPS_API_KEY=optional-maps-embed-key
```

3. Create Firestore users with `role` of `admin` or `responder`, or add staff from **Users** in the panel.

Expected collections: `users`, `reports`, `reports/{id}/updates`, `notifications`, `sms_reports`, `attractions`, `activities`, `events`, `emergency_contacts`.
