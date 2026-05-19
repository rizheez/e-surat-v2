<?php

namespace App\Http\Middleware;

use App\Enums\OutgoingLetterStatus;
use App\Models\OutgoingLetter;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user()?->loadMissing(['unit', 'position']),
                'permissions' => $request->user()?->getAllPermissions()->pluck('name')->values() ?? [],
                'roles' => $request->user()?->getRoleNames()->values() ?? [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'notifications' => fn () => $request->user()
                ? [
                    'unread_count' => $request->user()->unreadNotifications()->count(),
                    'pending_approvals_count' => OutgoingLetter::query()
                        ->where('content_mode', 'generate')
                        ->where('signatory_user_id', $request->user()->id)
                        ->where('status', OutgoingLetterStatus::MenungguPersetujuan)
                        ->count(),
                    'items' => $request->user()->notifications()
                        ->latest()
                        ->limit(5)
                        ->get()
                        ->map(fn (DatabaseNotification $notification) => [
                            'id' => $notification->id,
                            'type' => class_basename($notification->type),
                            'title' => data_get($notification->data, 'perihal', 'Notifikasi baru'),
                            'body' => data_get($notification->data, 'instruksi')
                                ?? data_get($notification->data, 'message')
                                ?? 'Ada pembaruan yang perlu Anda lihat.',
                            'sender' => data_get($notification->data, 'dari'),
                            'url' => data_get($notification->data, 'url'),
                            'read_at' => $notification->read_at?->toIso8601String(),
                            'created_at' => $notification->created_at?->toIso8601String(),
                            'batas_waktu' => data_get($notification->data, 'batas_waktu'),
                        ])
                        ->values(),
                ]
                : [
                    'unread_count' => 0,
                    'pending_approvals_count' => 0,
                    'items' => [],
                ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
