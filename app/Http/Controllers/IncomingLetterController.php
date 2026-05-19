<?php

namespace App\Http\Controllers;

use App\Enums\IncomingLetterStatus;
use App\Http\Requests\IncomingLetterRequest;
use App\Models\IncomingLetter;
use App\Models\LetterCategory;
use App\Models\LetterNature;
use App\Services\AgendaNumberService;
use App\Services\FileUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class IncomingLetterController extends Controller
{
    public function __construct(
        private readonly FileUploadService $fileService,
        private readonly AgendaNumberService $agendaService,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', IncomingLetter::class);

        $query = IncomingLetter::with(['nature', 'category', 'createdBy'])
            ->when($request->search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('perihal', 'like', "%{$search}%")
                        ->orWhere('nomor_surat', 'like', "%{$search}%")
                        ->orWhere('nomor_agenda', 'like', "%{$search}%")
                        ->orWhere('asal_surat', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn ($query, string $status) => $query->where('status', $status))
            ->when($request->sifat_id, fn ($query, string $natureId) => $query->where('sifat_surat_id', $natureId))
            ->when($request->date_from, fn ($query, string $date) => $query->whereDate('tanggal_diterima', '>=', $date))
            ->when($request->date_to, fn ($query, string $date) => $query->whereDate('tanggal_diterima', '<=', $date));

        if (!$request->user()->can('view confidential letters')) {
            $query->whereHas('nature', fn ($nature) => $nature->where('level_kerahasiaan', 0));
        }

        return Inertia::render('IncomingLetters/Index', [
            'letters' => $query->latest('tanggal_diterima')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (IncomingLetter $letter) => $this->presentLetter($letter)),
            'filters' => $request->only(['search', 'status', 'sifat_id', 'date_from', 'date_to']),
            'natures' => LetterNature::orderBy('nama')->get(),
            'categories' => LetterCategory::orderBy('kode')->get(),
            'statuses' => $this->statuses(IncomingLetterStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', IncomingLetter::class);

        return Inertia::render('IncomingLetters/Create', [
            'natures' => LetterNature::orderBy('nama')->get(),
            'categories' => LetterCategory::orderBy('kode')->get(),
        ]);
    }

    public function edit(IncomingLetter $incomingLetter): Response
    {
        $this->authorize('update', $incomingLetter);

        $incomingLetter->load(['nature', 'category']);

        return Inertia::render('IncomingLetters/Edit', [
            'letter' => $this->presentLetter($incomingLetter),
            'natures' => LetterNature::orderBy('nama')->get(),
            'categories' => LetterCategory::orderBy('kode')->get(),
            'statuses' => $this->statuses(IncomingLetterStatus::cases()),
        ]);
    }

    public function store(IncomingLetterRequest $request): RedirectResponse
    {
        $this->authorize('create', IncomingLetter::class);

        $data = $request->validated();

        DB::transaction(function () use ($request, $data) {
            $data['nomor_agenda'] = $this->agendaService->generate();
            $data['created_by'] = $request->user()->id;
            $data['status'] = $data['status'] ?? IncomingLetterStatus::Baru->value;
            unset($data['file_surat']);

            if ($request->hasFile('file_surat')) {
                $data['file_path'] = $this->fileService->uploadLetterFile($request->file('file_surat'), 'surat-masuk', $data['nomor_agenda']);
            }

            IncomingLetter::create($data);
        });

        return redirect()->route('incoming-letters.index')->with('success', 'Surat masuk berhasil ditambahkan.');
    }

    public function show(Request $request, IncomingLetter $incomingLetter): Response
    {
        $this->authorize('view', $incomingLetter);

        $incomingLetter->load(['nature', 'category', 'createdBy', 'dispositions.sender', 'dispositions.recipients.recipient', 'dispositions.followups.recipient']);

        return Inertia::render('IncomingLetters/Show', [
            'letter' => $this->presentLetter($incomingLetter),
        ]);
    }

    public function update(IncomingLetterRequest $request, IncomingLetter $incomingLetter): RedirectResponse
    {
        $this->authorize('update', $incomingLetter);

        $data = $request->validated();
        unset($data['file_surat']);

        if ($request->hasFile('file_surat')) {
            if ($incomingLetter->file_path) {
                Storage::disk('local')->delete($incomingLetter->file_path);
            }

            $data['file_path'] = $this->fileService->uploadLetterFile($request->file('file_surat'), 'surat-masuk', $incomingLetter->nomor_agenda);
        }

        $incomingLetter->update($data);

        return back()->with('success', 'Surat masuk berhasil diperbarui.');
    }

    public function destroy(IncomingLetter $incomingLetter): RedirectResponse
    {
        $this->authorize('delete', $incomingLetter);

        if ($incomingLetter->status === IncomingLetterStatus::Diarsipkan) {
            return back()->with('error', 'Surat yang sudah diarsipkan tidak dapat dihapus.');
        }

        if ($incomingLetter->file_path) {
            Storage::disk('local')->delete($incomingLetter->file_path);
        }

        $incomingLetter->delete();

        return back()->with('success', 'Surat masuk berhasil dihapus.');
    }

    public function file(IncomingLetter $incomingLetter)
    {
        $this->authorize('view', $incomingLetter);

        abort_unless($incomingLetter->file_path && Storage::disk('local')->exists($incomingLetter->file_path), 404);

        return Storage::disk('local')->response($incomingLetter->file_path, basename($incomingLetter->file_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($incomingLetter->file_path).'"',
        ]);
    }

    private function statuses(array $statuses): array
    {
        return array_map(fn ($status) => ['value' => $status->value, 'label' => $status->label()], $statuses);
    }

    private function presentLetter(IncomingLetter $letter): array
    {
        $data = $letter->toArray();
        $data['has_file'] = filled($letter->file_path);
        $data['file_url'] = $letter->file_path
            ? URL::temporarySignedRoute('incoming-letters.file', now()->addMinutes(30), $letter)
            : null;
        unset($data['file_path']);

        return $data;
    }
}
