<?php

namespace App\Http\Controllers;

use App\Enums\IncomingLetterStatus;
use App\Enums\OutgoingLetterStatus;
use App\Exports\ArchivesExport;
use App\Exports\DispositionsExport;
use App\Exports\IncomingLettersExport;
use App\Exports\LetterNumberReservationsExport;
use App\Exports\OutgoingLettersExport;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\LetterNumberReservation;
use App\Models\OutgoingLetter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportExportController extends Controller
{
    public function incoming(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()->can('export reports'), 403);
        $this->authorize('viewAny', IncomingLetter::class);

        $query = IncomingLetter::with(['nature', 'createdBy'])
            ->visibleTo($request->user())
            ->when($request->search, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('perihal', 'like', "%{$search}%")
                        ->orWhere('nomor_surat', 'like', "%{$search}%")
                        ->orWhere('nomor_agenda', 'like', "%{$search}%")
                        ->orWhere('asal_surat', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($request->sifat_id, fn (Builder $query, string $natureId) => $query->where('sifat_surat_id', $natureId))
            ->when($request->date_from, fn (Builder $query, string $date) => $query->whereDate('tanggal_diterima', '>=', $date))
            ->when($request->date_to, fn (Builder $query, string $date) => $query->whereDate('tanggal_diterima', '<=', $date));

        if (!$request->user()->can('view confidential letters')) {
            $query->whereHas('nature', fn (Builder $nature) => $nature->where('level_kerahasiaan', 0));
        }

        return Excel::download(
            new IncomingLettersExport($query->latest('tanggal_diterima')->get()),
            'laporan-surat-masuk.xlsx'
        );
    }

    public function outgoing(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()->can('export reports'), 403);
        $this->authorize('viewAny', OutgoingLetter::class);

        $query = OutgoingLetter::with(['category', 'createdBy', 'signatory.position'])
            ->when($request->search, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('perihal', 'like', "%{$search}%")
                        ->orWhere('nomor_surat_keluar', 'like', "%{$search}%")
                        ->orWhere('tujuan_surat', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($request->kategori_id, fn (Builder $query, string $categoryId) => $query->where('kategori_surat_id', $categoryId));

        return Excel::download(
            new OutgoingLettersExport($query->latest('tanggal_surat')->get()),
            'laporan-surat-keluar.xlsx'
        );
    }

    public function dispositions(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()->can('export reports'), 403);
        $this->authorize('viewAny', Disposition::class);

        $user = $request->user();
        $query = Disposition::with(['incomingLetter.nature', 'sender', 'recipients.recipient'])
            ->whereNull('parent_disposition_id')
            ->when(!$user->can('view all dispositions'), function (Builder $query) use ($user) {
                $query->where(function (Builder $query) use ($user) {
                    $query->where('sender_id', $user->id)
                        ->orWhereHas('recipients', fn (Builder $recipient) => $recipient->where('recipient_id', $user->id));
                });
            })
            ->when($request->search, function (Builder $query, string $search) {
                $query->whereHas('incomingLetter', function (Builder $letter) use ($search) {
                    $letter->where('perihal', 'like', "%{$search}%")
                        ->orWhere('nomor_agenda', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn (Builder $query, string $status) => $query->where('status', $status));

        return Excel::download(
            new DispositionsExport($query->latest('tanggal_disposisi')->get()),
            'laporan-disposisi.xlsx'
        );
    }

    public function archives(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()->can('export reports'), 403);
        abort_unless($request->user()->can('view archives'), 403);

        $incomingQuery = IncomingLetter::with(['nature', 'createdBy'])
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

        $outgoingQuery = OutgoingLetter::with(['category', 'createdBy'])
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

        $type = $request->string('type')->toString();
        if ($type === 'incoming') {
            $outgoingQuery->whereRaw('1 = 0');
        } elseif ($type === 'outgoing') {
            $incomingQuery->whereRaw('1 = 0');
        }

        $this->sortArchiveQuery($incomingQuery, $request, 'tanggal_diterima');
        $this->sortArchiveQuery($outgoingQuery, $request, 'tanggal_surat');

        return Excel::download(
            new ArchivesExport($this->archiveRows($incomingQuery->get(), $outgoingQuery->get())),
            'laporan-arsip.xlsx'
        );
    }

    public function letterNumberReservations(Request $request): BinaryFileResponse
    {
        abort_unless($request->user()->can('manage outgoing letters'), 403);

        $query = LetterNumberReservation::query()
            ->with(['category', 'createdBy', 'usedByOutgoingLetter'])
            ->when($request->search, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('nomor_surat', 'like', "%{$search}%")
                        ->orWhere('perihal', 'like', "%{$search}%")
                        ->orWhere('tujuan_surat', 'like', "%{$search}%")
                        ->orWhere('jenis_dokumen', 'like', "%{$search}%");
                });
            })
            ->when($request->status, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($request->kategori_id, fn (Builder $query, string $categoryId) => $query->where('kategori_surat_id', $categoryId));

        return Excel::download(
            new LetterNumberReservationsExport($query->latest('tanggal_surat')->latest()->get()),
            'laporan-penomoran-surat.xlsx'
        );
    }

    private function archiveRows(Collection $incomingLetters, Collection $outgoingLetters): Collection
    {
        return $incomingLetters->map(fn (IncomingLetter $letter) => [
            'jenis_arsip' => 'Surat Masuk',
            'nomor' => $letter->nomor_agenda,
            'tanggal_dokumen' => $letter->tanggal_diterima?->toDateString(),
            'asal_tujuan' => $letter->asal_surat,
            'perihal' => $letter->perihal,
            'kategori_sifat' => $letter->nature?->nama,
            'status' => $letter->status->label(),
            'dibuat_oleh' => $letter->createdBy?->name,
        ])->values()->concat(
            $outgoingLetters->map(fn (OutgoingLetter $letter) => [
                'jenis_arsip' => 'Surat Keluar',
                'nomor' => $letter->nomor_surat_keluar,
                'tanggal_dokumen' => $letter->tanggal_surat?->toDateString(),
                'asal_tujuan' => $letter->tujuan_surat,
                'perihal' => $letter->perihal,
                'kategori_sifat' => $letter->category?->nama,
                'status' => $letter->status->label(),
                'dibuat_oleh' => $letter->createdBy?->name,
            ])->values()
        )->values();
    }

    private function sortArchiveQuery(Builder $query, Request $request, string $dateColumn): void
    {
        match ($request->string('sort')->toString()) {
            'oldest' => $query->oldest($dateColumn),
            'number' => $query->orderBy($dateColumn === 'tanggal_diterima' ? 'nomor_agenda' : 'nomor_surat_keluar'),
            'subject' => $query->orderBy('perihal'),
            default => $query->latest($dateColumn),
        };
    }
}
