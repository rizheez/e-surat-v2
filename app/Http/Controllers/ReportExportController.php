<?php

namespace App\Http\Controllers;

use App\Enums\IncomingLetterStatus;
use App\Enums\OutgoingLetterStatus;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\OutgoingLetter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    public function incoming(Request $request): StreamedResponse
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

        return $this->csv('laporan-surat-masuk.csv', [
            'Nomor Agenda',
            'Nomor Surat',
            'Tanggal Surat',
            'Tanggal Diterima',
            'Asal Surat',
            'Perihal',
            'Sifat',
            'Status',
            'Dicatat Oleh',
        ], function ($handle) use ($query) {
            $query->latest('tanggal_diterima')->chunk(200, function ($letters) use ($handle) {
                foreach ($letters as $letter) {
                    fputcsv($handle, [
                        $letter->nomor_agenda,
                        $letter->nomor_surat,
                        $letter->tanggal_surat?->toDateString(),
                        $letter->tanggal_diterima?->toDateString(),
                        $letter->asal_surat,
                        $letter->perihal,
                        $letter->nature?->nama,
                        $letter->status->label(),
                        $letter->createdBy?->name,
                    ]);
                }
            });
        });
    }

    public function outgoing(Request $request): StreamedResponse
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

        return $this->csv('laporan-surat-keluar.csv', [
            'Nomor Surat',
            'Tanggal Surat',
            'Tujuan',
            'Perihal',
            'Kategori',
            'Status',
            'Penandatangan',
            'Jabatan Penandatangan',
            'Dibuat Oleh',
        ], function ($handle) use ($query) {
            $query->latest('tanggal_surat')->chunk(200, function ($letters) use ($handle) {
                foreach ($letters as $letter) {
                    fputcsv($handle, [
                        $letter->nomor_surat_keluar,
                        $letter->tanggal_surat?->toDateString(),
                        $letter->tujuan_surat,
                        $letter->perihal,
                        $letter->category?->nama,
                        $letter->status->label(),
                        $letter->penandatangan_nama ?: $letter->signatory?->name,
                        $letter->penandatangan_jabatan ?: $letter->signatory?->position?->nama,
                        $letter->createdBy?->name,
                    ]);
                }
            });
        });
    }

    public function dispositions(Request $request): StreamedResponse
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

        return $this->csv('laporan-disposisi.csv', [
            'Nomor Agenda',
            'Perihal Surat',
            'Pengirim Disposisi',
            'Penerima',
            'Instruksi',
            'Catatan',
            'Tanggal Disposisi',
            'Batas Waktu',
            'Status',
        ], function ($handle) use ($query) {
            $query->latest('tanggal_disposisi')->chunk(200, function ($dispositions) use ($handle) {
                foreach ($dispositions as $disposition) {
                    fputcsv($handle, [
                        $disposition->incomingLetter?->nomor_agenda,
                        $disposition->incomingLetter?->perihal,
                        $disposition->sender?->name,
                        $disposition->recipients->map(fn ($item) => $item->recipient?->name)->filter()->join(', '),
                        $disposition->instruksi,
                        $disposition->catatan,
                        $disposition->tanggal_disposisi?->toDateTimeString(),
                        $disposition->batas_waktu?->toDateString(),
                        $disposition->status->label(),
                    ]);
                }
            });
        });
    }

    public function archives(Request $request): StreamedResponse
    {
        abort_unless($request->user()->can('export reports'), 403);
        abort_unless($request->user()->can('view archives'), 403);

        $incomingQuery = IncomingLetter::with(['nature', 'createdBy'])
            ->visibleTo($request->user())
            ->where('status', IncomingLetterStatus::Diarsipkan->value)
            ->when(!$request->user()->can('view confidential letters'), fn (Builder $query) => $query->whereHas('nature', fn (Builder $nature) => $nature->where('level_kerahasiaan', 0)))
            ->when($request->year, fn (Builder $query, string $year) => $query->whereYear('tanggal_diterima', $year))
            ->when($request->month, fn (Builder $query, string $month) => $query->whereMonth('tanggal_diterima', $month))
            ->when($request->sifat_id, fn (Builder $query, string $natureId) => $query->where('sifat_surat_id', $natureId));

        $outgoingQuery = OutgoingLetter::with(['category', 'createdBy'])
            ->where('status', OutgoingLetterStatus::Diarsipkan->value)
            ->when($request->year, fn (Builder $query, string $year) => $query->whereYear('tanggal_surat', $year))
            ->when($request->month, fn (Builder $query, string $month) => $query->whereMonth('tanggal_surat', $month))
            ->when($request->kategori_id, fn (Builder $query, string $categoryId) => $query->where('kategori_surat_id', $categoryId));

        return $this->csv('laporan-arsip.csv', [
            'Jenis Arsip',
            'Nomor',
            'Tanggal Dokumen',
            'Asal/Tujuan',
            'Perihal',
            'Kategori/Sifat',
            'Status',
            'Dicatat/Dibuat Oleh',
        ], function ($handle) use ($incomingQuery, $outgoingQuery) {
            $incomingQuery->latest('tanggal_diterima')->chunk(200, function ($letters) use ($handle) {
                foreach ($letters as $letter) {
                    fputcsv($handle, [
                        'Surat Masuk',
                        $letter->nomor_agenda,
                        $letter->tanggal_diterima?->toDateString(),
                        $letter->asal_surat,
                        $letter->perihal,
                        $letter->nature?->nama,
                        $letter->status->label(),
                        $letter->createdBy?->name,
                    ]);
                }
            });

            $outgoingQuery->latest('tanggal_surat')->chunk(200, function ($letters) use ($handle) {
                foreach ($letters as $letter) {
                    fputcsv($handle, [
                        'Surat Keluar',
                        $letter->nomor_surat_keluar,
                        $letter->tanggal_surat?->toDateString(),
                        $letter->tujuan_surat,
                        $letter->perihal,
                        $letter->category?->nama,
                        $letter->status->label(),
                        $letter->createdBy?->name,
                    ]);
                }
            });
        });
    }

    private function csv(string $filename, array $headers, callable $writeRows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $writeRows) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            $writeRows($handle);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
