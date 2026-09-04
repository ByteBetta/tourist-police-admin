<?php

namespace Database\Seeders;

use App\Enums\ReportSource;
use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@touristpolice.ppc'],
            [
                'name' => 'Tourist Police Admin',
                'firebase_uid' => 'admin-uid',
                'role' => UserRole::Admin,
                'phone' => '09171234567',
                'password' => 'password',
                'is_disabled' => false,
            ],
        );

        $responder = User::query()->updateOrCreate(
            ['email' => 'responder@touristpolice.ppc'],
            [
                'name' => 'Responder Cruz',
                'firebase_uid' => 'responder-uid',
                'role' => UserRole::Responder,
                'phone' => '09179876543',
                'password' => 'password',
                'is_disabled' => false,
            ],
        );

        $now = now();

        $store = [
            'users' => [
                $admin->firebase_uid => [
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'phone' => $admin->phone,
                    'role' => UserRole::Admin->value,
                    'verified' => true,
                    'disabled' => false,
                    'createdAt' => $now->toIso8601String(),
                ],
                $responder->firebase_uid => [
                    'name' => $responder->name,
                    'email' => $responder->email,
                    'phone' => $responder->phone,
                    'role' => UserRole::Responder->value,
                    'verified' => true,
                    'disabled' => false,
                    'createdAt' => $now->toIso8601String(),
                ],
                'tourist-jane' => [
                    'name' => 'Jane Park',
                    'email' => 'jane.park@example.com',
                    'phone' => '+1-555-0134',
                    'role' => UserRole::Tourist->value,
                    'verified' => true,
                    'disabled' => false,
                    'createdAt' => $now->copy()->subDays(3)->toIso8601String(),
                ],
            ],
            'reports' => [
                'report-pending' => [
                    'title' => 'Lost passport near Rizal Park',
                    'description' => 'Tourist misplaced a passport after visiting Rizal Park. Last seen near the food stalls.',
                    'type' => ReportType::Concern->value,
                    'status' => ReportStatus::Pending->value,
                    'source' => ReportSource::App->value,
                    'touristId' => 'tourist-jane',
                    'touristName' => 'Jane Park',
                    'touristPhone' => '+1-555-0134',
                    'language' => 'en',
                    'photos' => ['https://picsum.photos/seed/ppc-passport/800/500'],
                    'location' => [
                        'lat' => 9.7392,
                        'lng' => 118.7353,
                        'address' => 'Rizal Park, Puerto Princesa City',
                    ],
                    'createdAt' => $now->copy()->subHours(2)->toIso8601String(),
                    'updatedAt' => $now->copy()->subHours(2)->toIso8601String(),
                ],
                'report-assigned' => [
                    'title' => 'Need transport assistance to the airport',
                    'description' => 'Family of four needs help arranging safe transport from the city proper to Puerto Princesa International Airport.',
                    'type' => ReportType::Assistance->value,
                    'status' => ReportStatus::Assigned->value,
                    'source' => ReportSource::App->value,
                    'touristId' => 'tourist-jane',
                    'touristName' => 'Jane Park',
                    'assignedTo' => 'responder-uid',
                    'assignedToName' => $responder->name,
                    'assignedBy' => 'admin-uid',
                    'assignedAt' => $now->copy()->subHour()->toIso8601String(),
                    'location' => [
                        'lat' => 9.7420,
                        'lng' => 118.7440,
                        'address' => 'City proper, Puerto Princesa',
                    ],
                    'createdAt' => $now->copy()->subHours(5)->toIso8601String(),
                    'updatedAt' => $now->copy()->subHour()->toIso8601String(),
                ],
                'report-resolved' => [
                    'title' => 'Medical assistance at Honda Bay waiting area',
                    'description' => 'Guest felt dizzy while waiting for a Honda Bay tour. Responder provided first aid and referred to a clinic.',
                    'type' => ReportType::Emergency->value,
                    'status' => ReportStatus::Resolved->value,
                    'source' => ReportSource::App->value,
                    'touristId' => 'tourist-jane',
                    'touristName' => 'Ken Watanabe',
                    'assignedTo' => 'responder-uid',
                    'assignedToName' => $responder->name,
                    'createdAt' => $now->copy()->subDay()->toIso8601String(),
                    'updatedAt' => $now->toIso8601String(),
                ],
            ],
            'reports/report-assigned/updates' => [
                'update-1' => [
                    'status' => ReportStatus::Assigned->value,
                    'note' => 'Assigned to Responder Cruz.',
                    'actorId' => 'admin-uid',
                    'actorName' => $admin->name,
                    'actorRole' => UserRole::Admin->value,
                    'createdAt' => $now->copy()->subHour()->toIso8601String(),
                ],
            ],
            'reports/report-resolved/updates' => [
                'update-2' => [
                    'status' => ReportStatus::Resolved->value,
                    'note' => 'Guest stabilized and referred to a nearby clinic.',
                    'actorId' => 'responder-uid',
                    'actorName' => $responder->name,
                    'actorRole' => UserRole::Responder->value,
                    'createdAt' => $now->toIso8601String(),
                ],
            ],
            'sms_reports' => [
                'sms-1' => [
                    'from' => '+639171112222',
                    'body' => 'HELP PPC Underground River waiting shed. Tourist injured. Loc: 10.1924,118.9260',
                    'location' => [
                        'lat' => 10.1924,
                        'lng' => 118.9260,
                        'address' => 'Puerto Princesa Underground River waiting area',
                    ],
                    'createdAt' => $now->copy()->subMinutes(40)->toIso8601String(),
                ],
            ],
            'attractions' => [
                'ugr' => [
                    'name' => 'Puerto Princesa Underground River',
                    'address' => 'Sabang, Puerto Princesa City',
                    'hours' => '8:00 AM – 3:30 PM',
                    'description' => 'UNESCO World Heritage underground river tour.',
                ],
                'honda' => [
                    'name' => 'Honda Bay',
                    'address' => 'Sta. Lourdes Wharf, Puerto Princesa City',
                    'hours' => '7:00 AM – 4:00 PM',
                    'description' => 'Island hopping and snorkeling tours.',
                ],
            ],
            'activities' => [
                'island-hop' => [
                    'name' => 'Honda Bay island hopping',
                    'location' => 'Honda Bay',
                    'duration' => '6 hours',
                    'description' => 'Island hopping with snorkeling stops.',
                ],
            ],
            'events' => [
                'baragatan' => [
                    'name' => 'Baragatan Festival cultural night',
                    'venue' => 'Provincial Capitol Grounds',
                    'startsAt' => $now->copy()->addDays(12)->toDateTimeString(),
                    'endsAt' => $now->copy()->addDays(12)->addHours(4)->toDateTimeString(),
                    'description' => 'Cultural performances and local food stalls.',
                ],
            ],
            'emergency_contacts' => [
                'tpo' => [
                    'name' => 'Tourist Police Office',
                    'agency' => 'Puerto Princesa Tourist Police',
                    'phone' => '(048) 434-2000',
                    'notes' => 'Primary tourist assistance desk.',
                ],
                'pdrrmo' => [
                    'name' => 'City Disaster Risk Reduction',
                    'agency' => 'PCDRRMO',
                    'phone' => '911',
                    'notes' => 'For life-threatening emergencies coordinate through Tourist Police.',
                ],
            ],
            'notifications' => [],
        ];

        $path = config('services.firebase.local_store', storage_path('app/firestore-local.json'));
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode($store, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
