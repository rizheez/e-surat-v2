<?php

namespace App\Http\Controllers;

use App\Enums\IncomingLetterStatus;
use App\Enums\OutgoingLetterStatus;
use App\Models\IncomingLetter;
use App\Models\LetterCategory;
use App\Models\LetterNature;
use App\Models\OutgoingLetter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class ArchiveController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $incomingQuery = $this->incomingArchiveQuery($request);
        $outgoingQuery = $this->outgoingArchiveQuery($request);
        $type = $request->string('type')->toString();

        if ($type === 'incoming') {
            $outgoingQuery->whereRaw('1 = 0');
        } elseif ($type === 'outgoing') {
            $incomingQuery->whereRaw('1 = 0');
        }

        $this->applySort($incomingQuery, $request, 'tanggal_diterima');
        $this->applySort($outgoingQuery, $request, 'tanggal_surat');

        return Inertia::render('Archives/Index', [
            'incomingLetters' => $incomingQuery
                ->paginate(8, ['*'], 'incoming_page')
                ->withQueryString()
                ->through(fn (IncomingLetter $letter) => $this->presentIncomingLetter($letter)),
            'outgoingLetters' => $outgoingQuery
                ->paginate(8, ['*'], 'outgoing_page')
                ->withQueryString()
                ->through(fn (OutgoingLetter $letter) => $this->presentOutgoingLetter($letter)),
            'filters' => $request->only(['search', 'year', 'month', 'date_from', 'date_to', 'type', 'sort', 'kategori_id', 'sifat_id']),
            'categories' => LetterCategory::orderBy('kode')->get(),
            'natures' => LetterNature::orderBy('nama')->get(),
        ]);
    }

    private function incomingArchiveQuery(Request $request): Builder
    {
        return IncomingLetter::with(['nature'])
            ->visibleTo($request->user())
            ->where('status', IncomingLetterStatus::Diarsipkan->value)
            ->when(!$request->user()->can('view confidential letters'), fn (Builder $query) => $query->whereHas('nature', fn (Builder $nature) => $nature->where('level_kerahasiaan', 0)))
            ->when($request->search, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('nomor_agenda', 'like', "%{$search}%")
                        ->orWhere('nomor_surat', 'like', "%{$search}%")
                        ->orWhere('asal_surat', 'like', "%{$search}%")
                        ->orWhere('perihal', 'like', "%{$search}%")
                        ->orWhere('ringkasan', 'like', "%{$search}%");
                });
            })
            ->when($request->year, fn (Builder $query, string $year) => $query->whereYear('tanggal_diterima', $year))
            ->when($request->month, fn (Builder $query, string $month) => $query->whereMonth('tanggal_diterima', $month))
            ->when($request->date_from, fn (Builder $query, string $date) => $query->whereDate('tanggal_diterima', '>=', $date))
            ->when($request->date_to, fn (Builder $query, string $date) => $query->whereDate('tanggal_diterima', '<=', $date))
            ->when($request->sifat_id, fn (Builder $query, string $natureId) => $query->where('sifat_surat_id', $natureId));
    }

    private function outgoingArchiveQuery(Request $request): Builder
    {
        return OutgoingLetter::with('category')
            ->where('status', OutgoingLetterStatus::Diarsipkan->value)
            ->when($request->search, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('nomor_surat_keluar', 'like', "%{$search}%")
                        ->orWhere('tujuan_surat', 'like', "%{$search}%")
                        ->orWhere('perihal', 'like', "%{$search}%")
                        ->orWhere('ringkasan', 'like', "%{$search}%");
                });
            })
            ->when($request->year, fn (Builder $query, string $year) => $query->whereYear('tanggal_surat', $year))
            ->when($request->month, fn (Builder $query, string $month) => $query->whereMonth('tanggal_surat', $month))
            ->when($request->date_from, fn (Builder $query, string $date) => $query->whereDate('tanggal_surat', '>=', $date))
            ->when($request->date_to, fn (Builder $query, string $date) => $query->whereDate('tanggal_surat', '<=', $date))
            ->when($request->kategori_id, fn (Builder $query, string $categoryId) => $query->where('kategori_surat_id', $categoryId));
    }

    private function applySort(Builder $query, Request $request, string $dateColumn): void
    {
        match ($request->string('sort')->toString()) {
            'oldest' => $query->oldest($dateColumn),
            'number' => $query->orderBy($dateColumn === 'tanggal_diterima' ? 'nomor_agenda' : 'nomor_surat_keluar'),
            'subject' => $query->orderBy('perihal'),
            default => $query->latest($dateColumn),
        };
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
