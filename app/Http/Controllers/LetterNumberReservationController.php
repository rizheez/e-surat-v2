<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBatchLetterNumberReservationRequest;
use App\Http\Requests\StoreLetterNumberReservationRequest;
use App\Models\LetterCategory;
use App\Models\LetterNumberReservation;
use App\Services\OutgoingLetterNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LetterNumberReservationController extends Controller
{
    public function __construct(
        private readonly OutgoingLetterNumberService $numberService,
    ) {
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->can('manage outgoing letters'), 403);

        $query = LetterNumberReservation::query()
            ->with(['category', 'createdBy', 'usedByOutgoingLetter'])
            ->when($request->search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('nomor_surat', 'like', "%{$search}%")
                        ->orWhere('perihal', 'like', "%{$search}%")
                        ->orWhere('tujuan_surat', 'like', "%{$search}%")
                        ->orWhere('jenis_dokumen', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn ($query, string $status) => $query->where('status', $status))
            ->when($request->kategori_id, fn ($query, string $categoryId) => $query->where('kategori_surat_id', $categoryId));

        return Inertia::render('LetterNumberReservations/Index', [
            'reservations' => $query
                ->latest('tanggal_surat')
                ->latest()
                ->paginate(10)
                ->withQueryString()
                ->through(fn (LetterNumberReservation $reservation) => $this->presentReservation($reservation)),
            'filters' => $request->only(['search', 'status', 'kategori_id']),
            'categories' => LetterCategory::orderBy('kode')->get(),
            'statuses' => [
                ['value' => 'reserved', 'label' => 'Belum dipakai'],
                ['value' => 'used', 'label' => 'Sudah dipakai'],
                ['value' => 'used_manual', 'label' => 'Dipakai manual'],
                ['value' => 'void', 'label' => 'Dibatalkan'],
            ],
        ]);
    }

    public function store(StoreLetterNumberReservationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $category = LetterCategory::query()->findOrFail($data['kategori_surat_id']);
        $date = Carbon::parse($data['tanggal_surat']);

        DB::transaction(function () use ($data, $category, $date, $request) {
            LetterNumberReservation::create([
                ...$data,
                'nomor_surat' => $this->numberService->generate($category, $date),
                'created_by' => $request->user()->id,
                'status' => 'reserved',
            ]);
        });

        return back()->with('success', 'Nomor surat berhasil digenerate dan direservasi.');
    }

    public function storeBatch(StoreBatchLetterNumberReservationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $category = LetterCategory::query()->findOrFail($data['kategori_surat_id']);
        $date = Carbon::parse($data['tanggal_surat']);
        $numbers = DB::transaction(function () use ($category, $date, $data, $request) {
            $numbers = $this->numberService->generateBatch($category, $date, $data['quantity']);

            foreach ($numbers as $number) {
                LetterNumberReservation::create([
                    'nomor_surat' => $number,
                    'tanggal_surat' => $date->toDateString(),
                    'kategori_surat_id' => $category->id,
                    'jenis_dokumen' => ($data['jenis_dokumen'] ?? null) ?: null,
                    'perihal' => $data['perihal'],
                    'tujuan_surat' => ($data['tujuan_surat'] ?? null) ?: null,
                    'catatan' => ($data['catatan'] ?? null) ?: null,
                    'created_by' => $request->user()->id,
                    'status' => 'reserved',
                ]);
            }

            return $numbers;
        });

        $firstNumber = $numbers[0];
        $lastNumber = $numbers[count($numbers) - 1];

        return back()->with('success', sprintf(
            '%d nomor surat berhasil digenerate dan direservasi (%s s.d. %s).',
            $data['quantity'],
            $firstNumber,
            $lastNumber,
        ));
    }

    public function markUsedManual(Request $request, LetterNumberReservation $letterNumberReservation): RedirectResponse
    {
        abort_unless($request->user()?->can('manage outgoing letters'), 403);

        if ($letterNumberReservation->status !== 'reserved') {
            return back()->with('error', 'Hanya nomor reservasi yang belum dipakai yang dapat ditandai sebagai dipakai manual.');
        }

        $letterNumberReservation->update([
            'status' => 'used_manual',
            'used_at' => now(),
        ]);

        return back()->with('success', 'Nomor surat berhasil ditandai sebagai dipakai manual.');
    }

    public function void(Request $request, LetterNumberReservation $letterNumberReservation): RedirectResponse
    {
        abort_unless($request->user()?->can('manage outgoing letters'), 403);

        if ($letterNumberReservation->status !== 'reserved') {
            return back()->with('error', 'Hanya nomor yang belum dipakai yang dapat dibatalkan.');
        }

        $letterNumberReservation->update(['status' => 'void']);

        return back()->with('success', 'Reservasi nomor surat berhasil dibatalkan.');
    }

    public static function presentReservation(LetterNumberReservation $reservation): array
    {
        $data = $reservation->toArray();
        $data['category'] = $reservation->category?->toArray();
        $data['createdBy'] = $reservation->createdBy?->toArray();
        $data['usedByOutgoingLetter'] = $reservation->usedByOutgoingLetter?->toArray();

        return $data;
    }
}
