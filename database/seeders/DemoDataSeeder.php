<?php

namespace Database\Seeders;

use App\Enums\DispositionStatus;
use App\Enums\IncomingLetterStatus;
use App\Enums\OutgoingLetterStatus;
use App\Models\ActivityLog;
use App\Models\Disposition;
use App\Models\DispositionFollowup;
use App\Models\IncomingLetter;
use App\Models\LetterCategory;
use App\Models\LetterNature;
use App\Models\OutgoingLetter;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\DispositionDeadlineReminder;
use App\Notifications\OutgoingLetterApprovalReminder;
use App\Notifications\OutgoingLetterApproved;
use App\Notifications\OutgoingLetterApprovalRequested;
use App\Notifications\OutgoingLetterNeedsRevision;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@esurat.test')->firstOrFail();
        $rektor = User::query()->where('email', 'rektor@esurat.test')->firstOrFail();
        $dekan = User::query()->where('email', 'dekanft@esurat.test')->firstOrFail();
        $dosen = User::query()->where('email', 'dosen@esurat.test')->firstOrFail();
        $tendik = User::query()->where('email', 'tendik@esurat.test')->firstOrFail();

        $feb = Unit::query()->where('kode', 'FEB')->firstOrFail();
        $lppm = Unit::query()->where('kode', 'LPPM')->firstOrFail();
        $upt = Unit::query()->where('kode', 'UPT-TI')->firstOrFail();

        $pimpinanFeb = $this->firstOrCreateUser('pimpinan-feb@esurat.test', 'Dekan FEB', $feb, 'Dekan FEB', 'pimpinan-fakultas');
        $kepalaLppm = $this->firstOrCreateUser('kepala-lppm@esurat.test', 'Kepala LPPM', $lppm, 'Kepala LPPM', 'pimpinan-unit');
        $stafUpt = $this->firstOrCreateUser('staf-upt@esurat.test', 'Staf UPT TI', $upt, 'Staf UPT TI', 'tendik');

        $natureBiasa = LetterNature::query()->where('kode', 'B')->firstOrFail();
        $naturePenting = LetterNature::query()->where('kode', 'P')->firstOrFail();
        $natureSegera = LetterNature::query()->where('kode', 'S')->firstOrFail();
        $natureRahasia = LetterNature::query()->where('kode', 'R')->firstOrFail();

        $catUnd = LetterCategory::query()->where('kode', 'UND')->firstOrFail();
        $catSk = LetterCategory::query()->where('kode', 'SK')->firstOrFail();
        $catSe = LetterCategory::query()->where('kode', 'SE')->firstOrFail();
        $catPrm = LetterCategory::query()->where('kode', 'PRM')->firstOrFail();
        $catNd = LetterCategory::query()->where('kode', 'ND')->firstOrFail();

        $incomingSpecs = [
            ['slug' => 'rapat-akreditasi', 'agenda' => '2026/101', 'number' => 'UND/BAN-PT/V/2026', 'letter_date' => now()->subDays(18), 'received_date' => now()->subDays(16), 'origin' => 'BAN-PT', 'subject' => 'Undangan koordinasi akreditasi prodi', 'summary' => 'Koordinasi persiapan visitasi akreditasi.', 'nature' => $naturePenting, 'status' => IncomingLetterStatus::Didisposisi, 'creator' => $admin],
            ['slug' => 'audit-internal', 'agenda' => '2026/102', 'number' => 'SE/AUDIT/V/2026', 'letter_date' => now()->subDays(14), 'received_date' => now()->subDays(13), 'origin' => 'Satuan Audit Internal', 'subject' => 'Jadwal audit tata kelola fakultas', 'summary' => 'Pemberitahuan audit internal semester genap.', 'nature' => $natureSegera, 'status' => IncomingLetterStatus::Diproses, 'creator' => $admin],
            ['slug' => 'hibah-penelitian', 'agenda' => '2026/103', 'number' => 'PRM/LPPM/V/2026', 'letter_date' => now()->subDays(9), 'received_date' => now()->subDays(8), 'origin' => 'LPPM Nasional', 'subject' => 'Permintaan proposal hibah penelitian', 'summary' => 'Permintaan pengusulan proposal hibah dosen.', 'nature' => $naturePenting, 'status' => IncomingLetterStatus::Didisposisi, 'creator' => $admin],
            ['slug' => 'serah-terima-aset', 'agenda' => '2026/104', 'number' => 'ND/ASET/V/2026', 'letter_date' => now()->subDays(6), 'received_date' => now()->subDays(5), 'origin' => 'Biro Umum', 'subject' => 'Serah terima aset laboratorium', 'summary' => 'Penjadwalan serah terima aset dan inventaris.', 'nature' => $natureBiasa, 'status' => IncomingLetterStatus::Selesai, 'creator' => $admin],
            ['slug' => 'permintaan-data-kerja-sama', 'agenda' => '2026/105', 'number' => 'PRM/KERJA-SAMA/V/2026', 'letter_date' => now()->subDays(4), 'received_date' => now()->subDays(3), 'origin' => 'Bagian Kerja Sama', 'subject' => 'Permintaan data kerja sama aktif', 'summary' => 'Rekap kerja sama untuk laporan pimpinan.', 'nature' => $natureRahasia, 'status' => IncomingLetterStatus::Didisposisi, 'creator' => $admin],
            ['slug' => 'undangan-workshop-ai', 'agenda' => '2026/106', 'number' => 'UND/AI/V/2026', 'letter_date' => now()->subDays(2), 'received_date' => now()->subDays(1), 'origin' => 'Direktorat Inovasi Digital', 'subject' => 'Undangan workshop AI kampus', 'summary' => 'Undangan workshop pemanfaatan AI untuk layanan kampus.', 'nature' => $natureBiasa, 'status' => IncomingLetterStatus::Baru, 'creator' => $admin],
        ];

        $letters = [];
        foreach ($incomingSpecs as $spec) {
            $letters[$spec['slug']] = IncomingLetter::query()->updateOrCreate(
                ['nomor_agenda' => $spec['agenda']],
                [
                    'nomor_surat' => $spec['number'],
                    'tanggal_surat' => $spec['letter_date']->toDateString(),
                    'tanggal_diterima' => $spec['received_date']->toDateString(),
                    'asal_surat' => $spec['origin'],
                    'perihal' => $spec['subject'],
                    'ringkasan' => $spec['summary'],
                    'sifat_surat_id' => $spec['nature']->id,
                    'status' => $spec['status']->value,
                    'created_by' => $spec['creator']->id,
                ],
            );
        }

        $dispositionA = $this->upsertDisposition(
            $letters['rapat-akreditasi'],
            $rektor,
            'Tindak lanjuti koordinasi akreditasi dan siapkan tim presentasi.',
            now()->subDays(16),
            now()->addDay(),
            DispositionStatus::Diproses,
        );
        $this->syncRecipient($dispositionA, $dekan, DispositionStatus::Diproses, now()->subDays(15), null);
        $this->logActivity('disposition.created', 'Disposisi akreditasi dibuat.', $rektor, $dispositionA, ['category' => 'demo']);
        $this->createFollowup($dispositionA, $dekan, 'Tim akreditasi sudah dibentuk dan jadwal simulasi disusun.', DispositionStatus::Diproses, now()->subDays(12));

        $childA = $this->upsertDisposition(
            $letters['rapat-akreditasi'],
            $dekan,
            'Koordinasikan dokumen prodi dan kesiapan dosen pendamping.',
            now()->subDays(14),
            now()->addDay(),
            DispositionStatus::Diproses,
            $dispositionA,
        );
        $this->syncRecipient($childA, $dosen, DispositionStatus::Diproses, now()->subDays(13), null);
        $this->createFollowup($childA, $dosen, 'Draft borang dan bahan presentasi sedang disempurnakan.', DispositionStatus::Diproses, now()->subDays(5));

        $dispositionB = $this->upsertDisposition(
            $letters['audit-internal'],
            $admin,
            'Siapkan dokumen audit dan koordinasi dengan unit terkait.',
            now()->subDays(13),
            now()->subDay(),
            DispositionStatus::Diproses,
        );
        $this->syncRecipient($dispositionB, $tendik, DispositionStatus::Diproses, now()->subDays(12), null);
        $this->createFollowup($dispositionB, $tendik, 'Dokumen audit terkumpul, tinggal final checking.', DispositionStatus::Diproses, now()->subDays(4));
        $tendik->notify(new DispositionDeadlineReminder($dispositionB->fresh('incomingLetter'), 'deadline_h2', now()->subDays(3)->toDateString()));

        $dispositionC = $this->upsertDisposition(
            $letters['hibah-penelitian'],
            $rektor,
            'Distribusikan informasi hibah ke dosen dan pilih proposal unggulan.',
            now()->subDays(8),
            now()->toDateString(),
            DispositionStatus::Menunggu,
        );
        $this->syncRecipient($dispositionC, $kepalaLppm, DispositionStatus::Diproses, now()->subDays(7), null);
        $this->syncRecipient($dispositionC, $dosen, DispositionStatus::Menunggu, null, null);
        $this->createFollowup($dispositionC, $kepalaLppm, 'Surat edaran hibah sudah dibagikan ke seluruh dosen.', DispositionStatus::Diproses, now()->subDays(2));

        $dispositionD = $this->upsertDisposition(
            $letters['serah-terima-aset'],
            $admin,
            'Pastikan BA serah terima ditandatangani dan arsipkan dokumen.',
            now()->subDays(5),
            now()->subDays(2),
            DispositionStatus::Selesai,
        );
        $this->syncRecipient($dispositionD, $stafUpt, DispositionStatus::Selesai, now()->subDays(5), now()->subDays(2));
        $this->createFollowup($dispositionD, $stafUpt, 'Serah terima selesai dan berita acara sudah diarsipkan.', DispositionStatus::Selesai, now()->subDays(2));

        $dispositionE = $this->upsertDisposition(
            $letters['permintaan-data-kerja-sama'],
            $rektor,
            'Kompilasi data kerja sama aktif untuk bahan rapat pimpinan.',
            now()->subDays(3),
            now()->addDays(2),
            DispositionStatus::Menunggu,
        );
        $this->syncRecipient($dispositionE, $pimpinanFeb, DispositionStatus::Menunggu, null, null);
        $this->syncRecipient($dispositionE, $admin, DispositionStatus::Diproses, now()->subDays(2), null);

        $outgoingSpecs = [
            ['number' => 'ND/201/UNU-KT/05/2026', 'date' => now()->subDays(12), 'target' => 'BAN-PT', 'subject' => 'Pengiriman dokumen akreditasi tahap akhir', 'summary' => 'Dokumen akreditasi siap dikirim ke BAN-PT.', 'category' => $catNd, 'creator' => $admin, 'signatory' => $rektor, 'status' => OutgoingLetterStatus::Disetujui, 'approval_requested_at' => now()->subDays(11), 'approved_at' => now()->subDays(10), 'content_mode' => 'generate'],
            ['number' => 'UND/202/UNU-KT/05/2026', 'date' => now()->subDays(9), 'target' => 'Seluruh Ketua Prodi', 'subject' => 'Undangan rapat koordinasi akreditasi', 'summary' => 'Rapat koordinasi final persiapan visitasi.', 'category' => $catUnd, 'creator' => $admin, 'signatory' => $rektor, 'status' => OutgoingLetterStatus::MenungguPersetujuan, 'approval_requested_at' => now()->subDays(4), 'approved_at' => null, 'content_mode' => 'generate'],
            ['number' => 'SE/203/UNU-KT/05/2026', 'date' => now()->subDays(7), 'target' => 'Sivitas Akademika FT', 'subject' => 'Edaran penggunaan ruang kelas selama audit', 'summary' => 'Penyesuaian penggunaan ruang selama audit internal.', 'category' => $catSe, 'creator' => $dekan, 'signatory' => $dekan, 'status' => OutgoingLetterStatus::PerluRevisi, 'approval_requested_at' => now()->subDays(5), 'approved_at' => null, 'content_mode' => 'generate', 'approval_note' => 'Tambahkan jadwal detail per ruangan.'],
            ['number' => 'PRM/204/UNU-KT/05/2026', 'date' => now()->subDays(3), 'target' => 'Direktorat Inovasi Digital', 'subject' => 'Permohonan narasumber workshop AI', 'summary' => 'Permohonan narasumber untuk workshop AI kampus.', 'category' => $catPrm, 'creator' => $admin, 'signatory' => $rektor, 'status' => OutgoingLetterStatus::Draft, 'approval_requested_at' => null, 'approved_at' => null, 'content_mode' => 'generate'],
            ['number' => 'SK/205/UNU-KT/04/2026', 'date' => now()->subDays(25), 'target' => 'Internal Universitas', 'subject' => 'SK panitia akreditasi fakultas', 'summary' => 'Penetapan panitia akreditasi fakultas.', 'category' => $catSk, 'creator' => $admin, 'signatory' => $rektor, 'status' => OutgoingLetterStatus::Diarsipkan, 'approval_requested_at' => now()->subDays(24), 'approved_at' => now()->subDays(23), 'content_mode' => 'generate'],
        ];

        $lettersOut = [];
        foreach ($outgoingSpecs as $spec) {
            $lettersOut[$spec['number']] = OutgoingLetter::query()->updateOrCreate(
                ['nomor_surat_keluar' => $spec['number']],
                [
                    'tanggal_surat' => $spec['date']->toDateString(),
                    'tujuan_surat' => $spec['target'],
                    'perihal' => $spec['subject'],
                    'ringkasan' => $spec['summary'],
                    'kategori_surat_id' => $spec['category']->id,
                    'signatory_user_id' => $spec['signatory']->id,
                    'content_mode' => $spec['content_mode'],
                    'lampiran_text' => '1 berkas',
                    'kepada_text' => $spec['target'],
                    'lokasi_tujuan' => 'di Tempat',
                    'salam_pembuka' => "Assalamu'alaikum Wr. Wb.",
                    'isi_surat' => 'Naskah demo surat keluar generated untuk presentasi sistem.',
                    'lampiran_detail' => 'Dokumen pendukung demo',
                    'penutup_text' => 'Demikian disampaikan, atas perhatian diucapkan terima kasih.',
                    'penandatangan_jabatan' => $spec['signatory']->position?->nama,
                    'penandatangan_nama' => $spec['signatory']->name,
                    'tembusan_text' => '1. Arsip',
                    'status' => $spec['status']->value,
                    'approval_requested_at' => $spec['approval_requested_at'],
                    'approved_at' => $spec['approved_at'],
                    'approval_note' => $spec['approval_note'] ?? null,
                    'verification_token' => in_array($spec['status'], [OutgoingLetterStatus::Disetujui, OutgoingLetterStatus::Diarsipkan], true) ? sha1($spec['number']) : null,
                    'verification_token_generated_at' => in_array($spec['status'], [OutgoingLetterStatus::Disetujui, OutgoingLetterStatus::Diarsipkan], true) ? ($spec['approved_at'] ?? $spec['date']) : null,
                    'created_by' => $spec['creator']->id,
                ],
            );
        }

        $this->logActivity('outgoing_letter.created', 'Draft surat akreditasi dibuat.', $admin, $lettersOut['ND/201/UNU-KT/05/2026'], ['category' => 'demo']);
        $this->logActivity('outgoing_letter.approved', 'Surat akreditasi disetujui pimpinan.', $rektor, $lettersOut['ND/201/UNU-KT/05/2026'], ['category' => 'demo']);
        $this->logActivity('outgoing_letter.approval_requested', 'Permintaan persetujuan rapat koordinasi dikirim.', $admin, $lettersOut['UND/202/UNU-KT/05/2026'], ['category' => 'demo']);
        $this->logActivity('outgoing_letter.needs_revision', 'Surat edaran dikembalikan untuk revisi.', $dekan, $lettersOut['SE/203/UNU-KT/05/2026'], ['approval_note' => 'Tambahkan jadwal detail per ruangan.', 'category' => 'demo']);
        $this->logActivity('outgoing_letter.created', 'Draft permohonan narasumber workshop dibuat.', $admin, $lettersOut['PRM/204/UNU-KT/05/2026'], ['category' => 'demo']);

        $rektor->notify(new OutgoingLetterApprovalRequested($lettersOut['UND/202/UNU-KT/05/2026']->fresh(['createdBy', 'signatory'])));
        $dekan->notify(new OutgoingLetterNeedsRevision($lettersOut['SE/203/UNU-KT/05/2026']->fresh(['createdBy', 'signatory'])));
        $admin->notify(new OutgoingLetterApproved($lettersOut['ND/201/UNU-KT/05/2026']->fresh(['createdBy', 'signatory'])));
        $rektor->notify(new OutgoingLetterApprovalReminder($lettersOut['UND/202/UNU-KT/05/2026']->fresh(['createdBy', 'signatory']), 'pending_approval', now()->toDateString()));
        $dekan->notify(new OutgoingLetterApprovalReminder($lettersOut['SE/203/UNU-KT/05/2026']->fresh(['createdBy', 'signatory']), 'pending_revision', now()->toDateString()));
    }

    private function firstOrCreateUser(string $email, string $name, Unit $unit, string $positionName, string $role): User
    {
        $position = Position::query()->firstOrCreate(
            ['nama' => $positionName, 'unit_id' => $unit->id],
            ['level' => 3],
        );

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'unit_id' => $unit->id,
                'position_id' => $position->id,
                'password' => 'password',
                'is_active' => true,
            ],
        );

        if (!$user->position_id) {
            $user->forceFill([
                'name' => $name,
                'unit_id' => $unit->id,
                'position_id' => $position->id,
                'is_active' => true,
            ])->save();
        }

        $user->syncRoles([$role]);

        return $user->fresh(['position', 'unit']);
    }

    private function upsertDisposition(
        IncomingLetter $letter,
        User $sender,
        string $instruction,
        Carbon $sentAt,
        Carbon|string|null $deadline,
        DispositionStatus $status,
        ?Disposition $parent = null,
    ): Disposition {
        return Disposition::query()->updateOrCreate(
            [
                'incoming_letter_id' => $letter->id,
                'sender_id' => $sender->id,
                'instruksi' => $instruction,
            ],
            [
                'parent_disposition_id' => $parent?->id,
                'catatan' => 'Data demo untuk presentasi.',
                'batas_waktu' => $deadline instanceof Carbon ? $deadline->toDateString() : $deadline,
                'status' => $status->value,
                'tanggal_disposisi' => $sentAt,
            ],
        );
    }

    private function syncRecipient(
        Disposition $disposition,
        User $recipient,
        DispositionStatus $status,
        ?Carbon $readAt,
        ?Carbon $finishedAt,
    ): void {
        $disposition->recipients()->updateOrCreate(
            ['recipient_id' => $recipient->id],
            [
                'unit_id' => $recipient->unit_id,
                'status' => $status->value,
                'tanggal_dibaca' => $readAt,
                'tanggal_selesai' => $finishedAt,
            ],
        );
    }

    private function createFollowup(
        Disposition $disposition,
        User $recipient,
        string $note,
        DispositionStatus $status,
        Carbon $createdAt,
    ): void {
        $followup = DispositionFollowup::query()->firstOrCreate(
            [
                'disposition_id' => $disposition->id,
                'recipient_id' => $recipient->id,
                'catatan' => $note,
            ],
            [
                'status' => $status->value,
            ],
        );

        $followup->forceFill([
            'status' => $status->value,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ])->save();
    }

    private function logActivity(string $logName, string $description, User $actor, object $subject, array $properties = []): void
    {
        ActivityLog::query()->updateOrCreate(
            [
                'log_name' => $logName,
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => $subject->id,
                'description' => $description,
            ],
            [
                'user_id' => $actor->id,
                'properties' => $properties,
            ],
        );
    }
}
