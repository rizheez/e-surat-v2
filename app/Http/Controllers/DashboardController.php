<?php

namespace App\Http\Controllers;

use App\Enums\DispositionStatus;
use App\Enums\IncomingLetterStatus;
use App\Enums\OutgoingLetterStatus;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\LetterCategory;
use App\Models\LetterNature;
use App\Models\OutgoingLetter;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const PERIODS = [
        '30d' => 30,
        '3m' => 90,
        '6m' => 180,
        '12m' => 365,
    ];

    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $period = array_key_exists($request->query('period'), self::PERIODS)
            ? (string) $request->query('period')
            : '12m';
        $startDate = now()->subDays(self::PERIODS[$period] - 1)->startOfDay();

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
            'filters' => [
                'period' => $period,
                'periodOptions' => [
                    ['value' => '30d', 'label' => '30 hari'],
                    ['value' => '3m', 'label' => '3 bulan'],
                    ['value' => '6m', 'label' => '6 bulan'],
                    ['value' => '12m', 'label' => '12 bulan'],
                ],
            ],
            'stats' => [
                'incoming_this_month' => IncomingLetter::whereMonth('tanggal_diterima', now()->month)->whereYear('tanggal_diterima', now()->year)->count(),
                'outgoing_this_month' => OutgoingLetter::whereMonth('tanggal_surat', now()->month)->whereYear('tanggal_surat', now()->year)->count(),
                'pending_dispositions' => (clone $dispositionQuery)->where('status', DispositionStatus::Menunggu->value)->count(),
                'processing_dispositions' => (clone $dispositionQuery)->where('status', DispositionStatus::Diproses->value)->count(),
                'completed_this_month' => (clone $dispositionQuery)->where('status', DispositionStatus::Selesai->value)->whereMonth('updated_at', now()->month)->count(),
                'undisposed_letters' => IncomingLetter::where('status', IncomingLetterStatus::Baru->value)->count(),
            ],
            'monitoring' => [
                'dispositions' => [
                    'overdue' => (clone $dispositionQuery)
                        ->where('status', '!=', DispositionStatus::Selesai->value)
                        ->whereDate('batas_waktu', '<', now()->toDateString())
                        ->count(),
                    'due_today' => (clone $dispositionQuery)
                        ->where('status', '!=', DispositionStatus::Selesai->value)
                        ->whereDate('batas_waktu', now()->toDateString())
                        ->count(),
                    'forwarded' => (clone $dispositionQuery)
                        ->whereHas('children')
                        ->count(),
                ],
                'approvals' => [
                    'pending' => OutgoingLetter::query()
                        ->where('content_mode', 'generate')
                        ->where('status', OutgoingLetterStatus::MenungguPersetujuan->value)
                        ->count(),
                    'revision' => OutgoingLetter::query()
                        ->where('content_mode', 'generate')
                        ->where('status', OutgoingLetterStatus::PerluRevisi->value)
                        ->count(),
                    'approved' => OutgoingLetter::query()
                        ->where('content_mode', 'generate')
                        ->where('status', OutgoingLetterStatus::Disetujui->value)
                        ->count(),
                    'stuck' => OutgoingLetter::query()
                        ->where('content_mode', 'generate')
                        ->where('status', OutgoingLetterStatus::MenungguPersetujuan->value)
                        ->where('approval_requested_at', '<=', now()->subDays(2))
                        ->count(),
                ],
            ],
            'monthlyLetters' => $monthlyLetters,
            'statusDistribution' => collect(DispositionStatus::cases())->map(fn ($status) => [
                'name' => $status->label(),
                'value' => Disposition::whereNull('parent_disposition_id')->where('status', $status->value)->count(),
            ]),
            'outgoingCategoryDistribution' => LetterCategory::query()
                ->withCount(['outgoingLetters as value' => fn ($query) => $query->whereDate('tanggal_surat', '>=', $startDate->toDateString())])
                ->orderByDesc('value')
                ->get()
                ->map(fn (LetterCategory $category) => ['name' => $category->kode, 'value' => $category->value]),
            'incomingNatureDistribution' => LetterNature::query()
                ->withCount(['incomingLetters as value' => fn ($query) => $query->whereDate('tanggal_diterima', '>=', $startDate->toDateString())])
                ->orderByDesc('value')
                ->get()
                ->map(fn (LetterNature $nature) => ['name' => $nature->nama, 'value' => $nature->value]),
            'dispositionTrend' => $this->dispositionTrend($startDate),
            'latestIncomingLetters' => IncomingLetter::with(['nature'])
                ->latest('tanggal_diterima')
                ->limit(5)
                ->get(),
            'latestDispositions' => Disposition::with(['incomingLetter', 'sender', 'recipients.recipient'])
                ->whereNull('parent_disposition_id')
                ->latest('tanggal_disposisi')
                ->limit(5)
                ->get()
                ->map(fn (Disposition $disposition) => $this->presentDisposition($disposition))
                ->values(),
            'latestApprovals' => OutgoingLetter::with(['createdBy', 'signatory.position', 'signatory.unit'])
                ->where('content_mode', 'generate')
                ->whereIn('status', [
                    OutgoingLetterStatus::MenungguPersetujuan->value,
                    OutgoingLetterStatus::PerluRevisi->value,
                    OutgoingLetterStatus::Disetujui->value,
                ])
                ->orderByRaw('coalesce(approval_requested_at, updated_at) desc')
                ->limit(5)
                ->get(),
            'alerts' => [
                'dueSoon' => Disposition::with('incomingLetter')
                    ->whereNull('parent_disposition_id')
                    ->where('status', '!=', DispositionStatus::Selesai->value)
                    ->whereBetween('batas_waktu', [now()->toDateString(), now()->addDays(2)->toDateString()])
                    ->limit(5)
                    ->get()
                    ->map(fn (Disposition $disposition) => $this->presentDisposition($disposition))
                    ->values(),
                'staleLetters' => IncomingLetter::where('status', IncomingLetterStatus::Baru->value)
                    ->whereDate('tanggal_diterima', '<=', now()->subDays(3)->toDateString())
                    ->limit(5)
                    ->get(),
                'stuckApprovals' => OutgoingLetter::with(['createdBy', 'signatory'])
                    ->where('content_mode', 'generate')
                    ->where('status', OutgoingLetterStatus::MenungguPersetujuan->value)
                    ->where('approval_requested_at', '<=', now()->subDays(2))
                    ->orderBy('approval_requested_at')
                    ->limit(5)
                    ->get(),
            ],
        ]);
    }

    private function dispositionTrend(CarbonInterface $startDate): array
    {
        return collect(range(5, 0))->map(function (int $monthsAgo) use ($startDate) {
            $date = now()->subMonths($monthsAgo);

            return [
                'month' => $date->format('M Y'),
                'selesai' => Disposition::whereNull('parent_disposition_id')
                    ->where('status', DispositionStatus::Selesai->value)
                    ->whereYear('updated_at', $date->year)
                    ->whereMonth('updated_at', $date->month)
                    ->count(),
                'overdue' => Disposition::whereNull('parent_disposition_id')
                    ->where('status', '!=', DispositionStatus::Selesai->value)
                    ->whereDate('batas_waktu', '>=', $startDate->toDateString())
                    ->whereYear('batas_waktu', $date->year)
                    ->whereMonth('batas_waktu', $date->month)
                    ->whereDate('batas_waktu', '<', now()->toDateString())
                    ->count(),
            ];
        })->values()->all();
    }

    private function presentDisposition(Disposition $disposition): array
    {
        $data = $disposition->toArray();
        $data['incomingLetter'] = $disposition->incomingLetter?->toArray();

        return $data;
    }
}
