<?php

namespace App\Http\Controllers;

use App\Enums\IncomingLetterStatus;
use App\Enums\OutgoingLetterStatus;
use App\Models\IncomingLetter;
use App\Models\LetterCategory;
use App\Models\LetterNature;
use App\Models\OutgoingLetter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class ArchiveController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $incomingQuery = IncomingLetter::with(['nature', 'category'])
            ->where('status', IncomingLetterStatus::Diarsipkan->value)
            ->when(!$request->user()->can('view confidential letters'), fn ($query) => $query->whereHas('nature', fn ($nature) => $nature->where('level_kerahasiaan', 0)))
            ->when($request->year, fn ($query, string $year) => $query->whereYear('tanggal_diterima', $year))
            ->when($request->month, fn ($query, string $month) => $query->whereMonth('tanggal_diterima', $month))
            ->when($request->kategori_id, fn ($query, string $categoryId) => $query->where('kategori_surat_id', $categoryId))
            ->when($request->sifat_id, fn ($query, string $natureId) => $query->where('sifat_surat_id', $natureId));

        $outgoingQuery = OutgoingLetter::with('category')
            ->where('status', OutgoingLetterStatus::Diarsipkan->value)
            ->when($request->year, fn ($query, string $year) => $query->whereYear('tanggal_surat', $year))
            ->when($request->month, fn ($query, string $month) => $query->whereMonth('tanggal_surat', $month))
            ->when($request->kategori_id, fn ($query, string $categoryId) => $query->where('kategori_surat_id', $categoryId));

        return Inertia::render('Archives/Index', [
            'incomingLetters' => $incomingQuery
                ->latest('tanggal_diterima')
                ->paginate(8, ['*'], 'incoming_page')
                ->withQueryString()
                ->through(fn (IncomingLetter $letter) => $this->presentIncomingLetter($letter)),
            'outgoingLetters' => $outgoingQuery
                ->latest('tanggal_surat')
                ->paginate(8, ['*'], 'outgoing_page')
                ->withQueryString()
                ->through(fn (OutgoingLetter $letter) => $this->presentOutgoingLetter($letter)),
            'filters' => $request->only(['year', 'month', 'kategori_id', 'sifat_id']),
            'categories' => LetterCategory::orderBy('kode')->get(),
            'natures' => LetterNature::orderBy('nama')->get(),
        ]);
    }

    private function presentIncomingLetter(IncomingLetter $letter): array
    {
        $data = $letter->toArray();
        $data['has_file'] = filled($letter->file_path);
        $data['file_url'] = $letter->file_path
            ? URL::temporarySignedRoute('incoming-letters.file', now()->addMinutes(30), $letter)
            : null;
        unset($data['file_path']);

        return $data;
    }

    private function presentOutgoingLetter(OutgoingLetter $letter): array
    {
        $data = $letter->toArray();
        $data['has_file'] = filled($letter->file_path);
        $data['file_url'] = $letter->file_path
            ? URL::temporarySignedRoute('outgoing-letters.file', now()->addMinutes(30), $letter)
            : null;
        $data['preview_url'] = route('outgoing-letters.preview', $letter);
        unset($data['file_path']);

        return $data;
    }
}
