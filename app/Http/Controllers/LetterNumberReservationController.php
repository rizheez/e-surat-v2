<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLetterNumberReservationRequest;
use App\Models\LetterCategory;
use App\Models\LetterNumberReservation;
use App\Services\OutgoingLetterNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                ['value' => 'void', 'label' => 'Dibatalkan'],
            ],
        ]);
    }

    public function store(StoreLetterNumberReservationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $category = LetterCategory::query()->findOrFail($data['kategori_surat_id']);
        $date = now()->parse($data['tanggal_surat']);

        LetterNumberReservation::create([
            ...$data,
            'nomor_surat' => $this->numberService->generate($category, $date),
            'created_by' => $request->user()->id,
            'status' => 'reserved',
        ]);

        return back()->with('success', 'Nomor surat berhasil digenerate dan direservasi.');
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
