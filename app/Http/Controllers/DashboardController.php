<?php

namespace App\Http\Controllers;

use App\Enums\DispositionStatus;
use App\Enums\IncomingLetterStatus;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        $dispositionQuery = Disposition::query()
            ->whereNull('parent_disposition_id')
            ->when(!$user->can('view all dispositions'), function ($query) use ($user) {
                $query->where(function ($query) use ($user) {
                    $query->where('sender_id', $user->id)
                        ->orWhereHas('recipients', fn ($recipient) => $recipient->where('recipient_id', $user->id));
                });
            });

        $monthlyLetters = collect(range(11, 0))->map(function (int $monthsAgo) {
            $date = now()->subMonths($monthsAgo);

            return [
                'month' => $date->format('M Y'),
                'masuk' => IncomingLetter::whereYear('tanggal_diterima', $date->year)->whereMonth('tanggal_diterima', $date->month)->count(),
                'keluar' => OutgoingLetter::whereYear('tanggal_surat', $date->year)->whereMonth('tanggal_surat', $date->month)->count(),
            ];
        });

        return Inertia::render('Dashboard', [
            'stats' => [
                'incoming_this_month' => IncomingLetter::whereMonth('tanggal_diterima', now()->month)->whereYear('tanggal_diterima', now()->year)->count(),
                'outgoing_this_month' => OutgoingLetter::whereMonth('tanggal_surat', now()->month)->whereYear('tanggal_surat', now()->year)->count(),
                'pending_dispositions' => (clone $dispositionQuery)->where('status', DispositionStatus::Menunggu->value)->count(),
                'processing_dispositions' => (clone $dispositionQuery)->where('status', DispositionStatus::Diproses->value)->count(),
                'completed_this_month' => (clone $dispositionQuery)->where('status', DispositionStatus::Selesai->value)->whereMonth('updated_at', now()->month)->count(),
                'undisposed_letters' => IncomingLetter::where('status', IncomingLetterStatus::Baru->value)->count(),
            ],
            'monthlyLetters' => $monthlyLetters,
            'statusDistribution' => collect(DispositionStatus::cases())->map(fn ($status) => [
                'name' => $status->label(),
                'value' => Disposition::whereNull('parent_disposition_id')->where('status', $status->value)->count(),
            ]),
            'latestIncomingLetters' => IncomingLetter::with(['nature', 'category'])
                ->latest('tanggal_diterima')
                ->limit(5)
                ->get(),
            'latestDispositions' => Disposition::with(['incomingLetter', 'sender', 'recipients.recipient'])
                ->whereNull('parent_disposition_id')
                ->latest('tanggal_disposisi')
                ->limit(5)
                ->get(),
            'alerts' => [
                'dueSoon' => Disposition::with('incomingLetter')
                    ->whereNull('parent_disposition_id')
                    ->where('status', '!=', DispositionStatus::Selesai->value)
                    ->whereBetween('batas_waktu', [now()->toDateString(), now()->addDays(2)->toDateString()])
                    ->limit(5)
                    ->get(),
                'staleLetters' => IncomingLetter::where('status', IncomingLetterStatus::Baru->value)
                    ->whereDate('tanggal_diterima', '<=', now()->subDays(3)->toDateString())
                    ->limit(5)
                    ->get(),
            ],
        ]);
    }
}
