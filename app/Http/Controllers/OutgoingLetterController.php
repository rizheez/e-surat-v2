<?php

namespace App\Http\Controllers;

use App\Enums\OutgoingLetterStatus;
use App\Http\Requests\OutgoingLetterRequest;
use App\Models\ActivityLog;
use App\Models\LetterCategory;
use App\Models\OutgoingLetter;
use App\Models\User;
use App\Notifications\OutgoingLetterApproved;
use App\Notifications\OutgoingLetterApprovalRequested;
use App\Notifications\OutgoingLetterNeedsRevision;
use App\Services\FileUploadService;
use App\Services\OutgoingLetterNumberService;
use App\Services\SignatureQrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class OutgoingLetterController extends Controller
{
    public function __construct(
        private readonly FileUploadService $fileService,
        private readonly OutgoingLetterNumberService $numberService,
        private readonly SignatureQrCodeService $signatureQrCodeService,
    ) {
    }

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', OutgoingLetter::class);

        $query = OutgoingLetter::with(['category', 'createdBy', 'signatory.position', 'signatory.unit'])
            ->when($request->search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('perihal', 'like', "%{$search}%")
                        ->orWhere('nomor_surat_keluar', 'like', "%{$search}%")
                        ->orWhere('tujuan_surat', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn ($query, string $status) => $query->where('status', $status))
            ->when($request->kategori_id, fn ($query, string $categoryId) => $query->where('kategori_surat_id', $categoryId));

        return Inertia::render('OutgoingLetters/Index', [
            'letters' => $query->latest('tanggal_surat')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (OutgoingLetter $letter) => $this->presentLetter($letter)),
            'filters' => $request->only(['search', 'status', 'kategori_id']),
            'categories' => LetterCategory::orderBy('kode')->get(),
            'signatories' => $this->signatories(),
            'statuses' => array_map(fn ($status) => ['value' => $status->value, 'label' => $status->label()], OutgoingLetterStatus::cases()),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', OutgoingLetter::class);

        return Inertia::render('OutgoingLetters/Create', [
            'categories' => LetterCategory::orderBy('kode')->get(),
            'signatories' => $this->signatories(),
            'statuses' => array_map(fn ($status) => ['value' => $status->value, 'label' => $status->label()], OutgoingLetterStatus::cases()),
            'initialNumber' => null,
        ]);
    }

    public function approvals(Request $request): Response
    {
        $this->authorize('viewAny', OutgoingLetter::class);

        $query = OutgoingLetter::with(['category', 'createdBy', 'signatory.position', 'signatory.unit'])
            ->where('content_mode', 'generate')
            ->where('signatory_user_id', $request->user()->id)
            ->when($request->search, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('perihal', 'like', "%{$search}%")
                        ->orWhere('nomor_surat_keluar', 'like', "%{$search}%")
                        ->orWhere('tujuan_surat', 'like', "%{$search}%");
                });
            })
            ->when(
                $request->status,
                fn ($query, string $status) => $query->where('status', $status),
                fn ($query) => $query->whereIn('status', [
                    OutgoingLetterStatus::MenungguPersetujuan,
                    OutgoingLetterStatus::Disetujui,
                    OutgoingLetterStatus::PerluRevisi,
                ]),
            );

        return Inertia::render('OutgoingLetters/Approvals', [
            'letters' => $query->latest('approval_requested_at')
                ->latest('tanggal_surat')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (OutgoingLetter $letter) => $this->presentLetter($letter)),
            'filters' => $request->only(['search', 'status']),
            'statuses' => collect(OutgoingLetterStatus::cases())
                ->filter(fn ($status) => in_array($status, [
                    OutgoingLetterStatus::MenungguPersetujuan,
                    OutgoingLetterStatus::PerluRevisi,
                    OutgoingLetterStatus::Disetujui,
                ], true))
                ->map(fn ($status) => ['value' => $status->value, 'label' => $status->label()])
                ->values(),
        ]);
    }

    public function edit(OutgoingLetter $outgoingLetter): Response
    {
        $this->authorize('update', $outgoingLetter);

        return Inertia::render('OutgoingLetters/Edit', [
            'letter' => $this->presentLetter($outgoingLetter),
            'categories' => LetterCategory::orderBy('kode')->get(),
            'signatories' => $this->signatories(),
            'statuses' => array_map(fn ($status) => ['value' => $status->value, 'label' => $status->label()], OutgoingLetterStatus::cases()),
        ]);
    }

    public function show(OutgoingLetter $outgoingLetter): Response
    {
        $this->authorize('view', $outgoingLetter);

        $outgoingLetter->loadMissing(['category', 'createdBy.unit', 'createdBy.position', 'signatory.unit', 'signatory.position']);

        return Inertia::render('OutgoingLetters/Show', [
            'letter' => $this->presentLetter($outgoingLetter),
            'activities' => $this->activitiesFor($outgoingLetter),
        ]);
    }

    public function store(OutgoingLetterRequest $request): RedirectResponse
    {
        $this->authorize('create', OutgoingLetter::class);

        $data = $request->validated();
        unset($data['file_surat']);

        $category = LetterCategory::findOrFail($data['kategori_surat_id']);
        $data['nomor_surat_keluar'] = $this->numberService->generate($category, now()->parse($data['tanggal_surat']));
        $data['created_by'] = $request->user()->id;
        $data['status'] = $data['status'] ?? OutgoingLetterStatus::Draft->value;
        if (in_array($data['status'], [OutgoingLetterStatus::MenungguPersetujuan->value, OutgoingLetterStatus::Disetujui->value], true)) {
            $data['status'] = OutgoingLetterStatus::Draft->value;
        }
        $data['approval_note'] = null;
        $this->syncSignatoryData($data);

        if ($data['content_mode'] === 'upload' && $request->hasFile('file_surat')) {
            $data['file_path'] = $this->fileService->uploadLetterFile($request->file('file_surat'), 'surat-keluar', $data['nomor_surat_keluar']);
        }

        $letter = OutgoingLetter::create($data);
        $this->logActivity('created', $letter, $request->user(), $request->user()->name.' membuat draft surat keluar.');

        return redirect()->route('outgoing-letters.index')->with('success', 'Surat keluar berhasil ditambahkan.');
    }

    public function update(OutgoingLetterRequest $request, OutgoingLetter $outgoingLetter): RedirectResponse
    {
        $this->authorize('update', $outgoingLetter);

        $data = $request->validated();
        unset($data['file_surat']);

        $category = LetterCategory::findOrFail($data['kategori_surat_id']);
        $data['nomor_surat_keluar'] = $this->numberService->generate(
            $category,
            now()->parse($data['tanggal_surat']),
            $outgoingLetter,
        );
        if (isset($data['status']) && in_array($data['status'], [OutgoingLetterStatus::MenungguPersetujuan->value, OutgoingLetterStatus::Disetujui->value], true)) {
            $data['status'] = OutgoingLetterStatus::Draft;
        }
        $this->syncSignatoryData($data);

        if (in_array($outgoingLetter->status, [OutgoingLetterStatus::MenungguPersetujuan, OutgoingLetterStatus::Disetujui, OutgoingLetterStatus::PerluRevisi], true)) {
            $data['status'] = OutgoingLetterStatus::Draft;
            $data['approval_requested_at'] = null;
            $data['approved_at'] = null;
            $data['approval_note'] = null;
        }

        if ($data['content_mode'] === 'generate' && $outgoingLetter->file_path) {
            Storage::disk('local')->delete($outgoingLetter->file_path);
            $data['file_path'] = null;
        }

        if ($data['content_mode'] === 'upload' && $request->hasFile('file_surat')) {
            if ($outgoingLetter->file_path) {
                Storage::disk('local')->delete($outgoingLetter->file_path);
            }

            $data['file_path'] = $this->fileService->uploadLetterFile($request->file('file_surat'), 'surat-keluar', $data['nomor_surat_keluar']);
        }

        $outgoingLetter->update($data);
        $this->logActivity('updated', $outgoingLetter, $request->user(), $request->user()->name.' memperbarui surat keluar.');

        return back()->with('success', 'Surat keluar berhasil diperbarui.');
    }

    public function submitApproval(OutgoingLetter $outgoingLetter): RedirectResponse
    {
        $this->authorize('submitApproval', $outgoingLetter);

        if ($outgoingLetter->content_mode !== 'generate') {
            return back()->with('error', 'Persetujuan tanda tangan hanya tersedia untuk surat generate web.');
        }

        if (!$outgoingLetter->signatory_user_id) {
            return back()->with('error', 'Pilih penandatangan terlebih dahulu.');
        }

        $outgoingLetter->forceFill([
            'status' => OutgoingLetterStatus::MenungguPersetujuan,
            'approval_requested_at' => now(),
            'approved_at' => null,
            'approval_note' => null,
        ])->save();
        $this->logActivity('approval_requested', $outgoingLetter, request()->user(), request()->user()->name.' mengajukan persetujuan surat keluar.');

        $outgoingLetter->loadMissing(['createdBy', 'signatory']);
        $outgoingLetter->signatory?->notify(new OutgoingLetterApprovalRequested($outgoingLetter));

        return back()->with('success', 'Permintaan persetujuan berhasil dikirim ke penandatangan.');
    }

    public function approve(Request $request, OutgoingLetter $outgoingLetter): RedirectResponse
    {
        $this->authorize('approve', $outgoingLetter);

        if ($outgoingLetter->content_mode !== 'generate') {
            return back()->with('error', 'Surat ini tidak memakai mode generate web.');
        }

        $outgoingLetter->forceFill([
            'status' => OutgoingLetterStatus::Disetujui,
            'approved_at' => now(),
            'approval_note' => null,
        ])->save();
        $this->logActivity('approved', $outgoingLetter, $request->user(), $request->user()->name.' menyetujui surat keluar.');

        $outgoingLetter->loadMissing(['createdBy', 'signatory']);
        $outgoingLetter->createdBy?->notify(new OutgoingLetterApproved($outgoingLetter));

        return back()->with('success', 'Surat keluar berhasil disetujui. QR tanda tangan siap dipakai di preview.');
    }

    public function reject(Request $request, OutgoingLetter $outgoingLetter): RedirectResponse
    {
        $this->authorize('reject', $outgoingLetter);

        $data = $request->validate([
            'approval_note' => ['required', 'string', 'max:2000'],
        ]);

        $outgoingLetter->forceFill([
            'status' => OutgoingLetterStatus::PerluRevisi,
            'approved_at' => null,
            'approval_note' => $data['approval_note'],
        ])->save();
        $this->logActivity('needs_revision', $outgoingLetter, $request->user(), $request->user()->name.' mengembalikan surat untuk revisi.', [
            'approval_note' => $data['approval_note'],
        ]);

        $outgoingLetter->loadMissing(['createdBy', 'signatory']);
        $outgoingLetter->createdBy?->notify(new OutgoingLetterNeedsRevision($outgoingLetter));

        return back()->with('success', 'Catatan revisi berhasil dikirim ke pembuat surat.');
    }

    public function destroy(OutgoingLetter $outgoingLetter): RedirectResponse
    {
        $this->authorize('delete', $outgoingLetter);

        if ($outgoingLetter->status === OutgoingLetterStatus::Diarsipkan) {
            return back()->with('error', 'Surat keluar yang sudah diarsipkan tidak dapat dihapus.');
        }

        if ($outgoingLetter->file_path) {
            Storage::disk('local')->delete($outgoingLetter->file_path);
        }

        $this->logActivity('deleted', $outgoingLetter, request()->user(), request()->user()->name.' menghapus surat keluar.');
        $outgoingLetter->delete();

        return back()->with('success', 'Surat keluar berhasil dihapus.');
    }

    public function file(OutgoingLetter $outgoingLetter)
    {
        $this->authorize('view', $outgoingLetter);

        abort_unless($outgoingLetter->file_path && Storage::disk('local')->exists($outgoingLetter->file_path), 404);

        return Storage::disk('local')->response($outgoingLetter->file_path, basename($outgoingLetter->file_path), [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.basename($outgoingLetter->file_path).'"',
        ]);
    }

    public function preview(Request $request, OutgoingLetter $outgoingLetter)
    {
        $this->authorize('view', $outgoingLetter);

        if ($outgoingLetter->content_mode === 'upload' && $outgoingLetter->file_path) {
            return redirect(URL::temporarySignedRoute('outgoing-letters.file', now()->addMinutes(30), $outgoingLetter));
        }

        return response()->view('outgoing_letters.preview', [
            'letter' => $outgoingLetter->loadMissing(['category', 'signatory.position', 'signatory.unit']),
            'previewDate' => now(),
            'headerImageSrc' => asset('brand/header-kop.png'),
            'footerImageSrc' => asset('brand/footer-kop.png'),
            'signatureQrSvg' => $this->signatureQrCodeService->generateSvg($outgoingLetter->loadMissing('signatory')),
        ]);
    }

    public function downloadPdf(OutgoingLetter $outgoingLetter): HttpResponse
    {
        $this->authorize('view', $outgoingLetter);

        if (
            $outgoingLetter->content_mode !== 'generate'
            || $outgoingLetter->status !== OutgoingLetterStatus::Disetujui
        ) {
            abort(403, 'PDF final hanya tersedia untuk surat generated web yang sudah disetujui.');
        }

        $outgoingLetter->loadMissing(['category', 'signatory.position', 'signatory.unit']);

        $pdf = Pdf::loadView('outgoing_letters.preview', [
            'letter' => $outgoingLetter,
            'previewDate' => now(),
            'headerImageSrc' => $this->inlineImage(public_path('brand/header-kop.png')),
            'footerImageSrc' => $this->inlineImage(public_path('brand/footer-kop.png')),
            'signatureQrSvg' => $this->signatureQrCodeService->generateSvg($outgoingLetter),
            'isPdf' => true,
        ])->setPaper('a4');
        $this->logActivity('pdf_downloaded', $outgoingLetter, request()->user(), request()->user()->name.' mengunduh PDF final surat keluar.');

        return $pdf->download($this->pdfFileName($outgoingLetter));
    }

    public function numberPreview(Request $request)
    {
        $categoryId = $request->integer('kategori_surat_id');
        $date = $request->date('tanggal_surat');

        abort_unless($categoryId && $date, 422);

        $category = LetterCategory::findOrFail($categoryId);
        $letter = $request->integer('outgoing_letter_id')
            ? OutgoingLetter::find($request->integer('outgoing_letter_id'))
            : null;

        return response()->json([
            'number' => $this->numberService->generate($category, $date, $letter),
        ]);
    }

    private function presentLetter(OutgoingLetter $letter): array
    {
        $data = $letter->toArray();
        $data['has_file'] = filled($letter->file_path);
        $data['file_url'] = $letter->file_path
            ? URL::temporarySignedRoute('outgoing-letters.file', now()->addMinutes(30), $letter)
            : null;
        $data['preview_url'] = route('outgoing-letters.preview', $letter);
        $data['pdf_download_url'] = $letter->content_mode === 'generate' && $letter->status === OutgoingLetterStatus::Disetujui
            ? route('outgoing-letters.pdf', $letter)
            : null;
        $data['signatory'] = $letter->signatory?->loadMissing(['position', 'unit'])?->toArray();
        unset($data['file_path']);

        return $data;
    }

    private function signatories()
    {
        return User::query()
            ->with(['position', 'unit'])
            ->permission('view outgoing letters')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    private function syncSignatoryData(array &$data): void
    {
        $signatory = isset($data['signatory_user_id'])
            ? User::query()->with('position')->find($data['signatory_user_id'])
            : null;

        $data['penandatangan_nama'] = $signatory?->name;
        $data['penandatangan_jabatan'] = $signatory?->position?->nama;
    }

    private function activitiesFor(OutgoingLetter $letter): array
    {
        return ActivityLog::with('user')
            ->where('subject_type', $letter->getMorphClass())
            ->where('subject_id', $letter->id)
            ->latest()
            ->get()
            ->map(fn (ActivityLog $activity) => [
                'id' => $activity->id,
                'log_name' => $activity->log_name,
                'description' => $activity->description,
                'created_at' => $activity->created_at?->toIso8601String(),
                'user' => $activity->user,
                'properties' => $activity->properties,
            ])
            ->all();
    }

    private function logActivity(string $name, OutgoingLetter $letter, ?User $actor, string $description, array $properties = []): void
    {
        ActivityLog::create([
            'user_id' => $actor?->id,
            'log_name' => 'outgoing_letter.'.$name,
            'description' => $description,
            'subject_type' => $letter->getMorphClass(),
            'subject_id' => $letter->id,
            'properties' => $properties,
        ]);
    }

    private function inlineImage(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        $mime = mime_content_type($path) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode((string) file_get_contents($path));
    }

    private function pdfFileName(OutgoingLetter $letter): string
    {
        $sanitizedNumber = str_replace(['/', '\\'], '-', $letter->nomor_surat_keluar);

        return 'surat-keluar-'.$sanitizedNumber.'.pdf';
    }
}
