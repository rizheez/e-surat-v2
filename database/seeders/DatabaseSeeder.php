<?php

namespace Database\Seeders;

use App\Models\ArchiveClassification;
use App\Models\DispositionInstruction;
use App\Models\IncomingLetter;
use App\Models\LetterCategory;
use App\Models\LetterNature;
use App\Models\Position;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view dashboard',
            'manage users',
            'manage master data',
            'view incoming letters',
            'view all incoming letters',
            'create incoming letters',
            'update incoming letters',
            'delete incoming letters',
            'view confidential letters',
            'create disposition',
            'view disposition',
            'view all dispositions',
            'update disposition status',
            'create followup',
            'view outgoing letters',
            'manage outgoing letters',
            'view archives',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        Role::firstOrCreate(['name' => 'super-admin'])->syncPermissions(Permission::all());
        Role::firstOrCreate(['name' => 'admin-persuratan'])->syncPermissions([
            'view dashboard',
            'view incoming letters',
            'view all incoming letters',
            'create incoming letters',
            'update incoming letters',
            'delete incoming letters',
            'view confidential letters',
            'view disposition',
            'view all dispositions',
            'view outgoing letters',
            'manage outgoing letters',
            'view archives',
        ]);

        foreach (['pimpinan-universitas', 'pimpinan-fakultas', 'pimpinan-prodi', 'pimpinan-unit'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName])->syncPermissions([
                'view dashboard',
                'view incoming letters',
                'view all incoming letters',
                'view confidential letters',
                'create disposition',
                'view disposition',
                'view all dispositions',
                'update disposition status',
                'view outgoing letters',
                'view archives',
            ]);
        }

        foreach (['dosen', 'tendik'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName])->syncPermissions([
                'view dashboard',
                'view incoming letters',
                'view disposition',
                'update disposition status',
                'create followup',
                'view archives',
            ]);
        }

        foreach ([
            ['nama' => 'Rektorat', 'kode' => 'RKT', 'is_cross_unit_target' => false],
            ['nama' => 'Fakultas Teknik', 'kode' => 'FT', 'is_cross_unit_target' => false],
            ['nama' => 'Fakultas Ekonomi dan Bisnis', 'kode' => 'FEB', 'is_cross_unit_target' => false],
            ['nama' => 'Biro Administrasi Umum', 'kode' => 'BAU', 'is_cross_unit_target' => false],
            ['nama' => 'Lembaga Penelitian dan Pengabdian', 'kode' => 'LPPM', 'is_cross_unit_target' => false],
            ['nama' => 'UPT Teknologi Informasi', 'kode' => 'UPT-TI', 'is_cross_unit_target' => true],
        ] as $unit) {
            Unit::updateOrCreate(['kode' => $unit['kode']], $unit);
        }

        $rektorat = Unit::where('kode', 'RKT')->first();
        $ft = Unit::where('kode', 'FT')->first();
        $bau = Unit::where('kode', 'BAU')->first();

        foreach ([
            ['nama' => 'Rektor', 'level' => 1, 'unit_id' => $rektorat->id],
            ['nama' => 'Kepala Biro Administrasi Umum', 'level' => 2, 'unit_id' => $bau->id],
            ['nama' => 'Dekan Fakultas Teknik', 'level' => 2, 'unit_id' => $ft->id],
            ['nama' => 'Dosen', 'level' => 5, 'unit_id' => $ft->id],
            ['nama' => 'Tenaga Kependidikan', 'level' => 5, 'unit_id' => $bau->id],
        ] as $position) {
            Position::firstOrCreate(['nama' => $position['nama'], 'unit_id' => $position['unit_id']], $position);
        }

        foreach ([
            ['nama' => 'Surat Undangan', 'kode' => 'UND', 'deskripsi' => 'Undangan kegiatan, rapat, atau seremoni.'],
            ['nama' => 'Surat Keputusan', 'kode' => 'SK', 'deskripsi' => 'Produk keputusan resmi.'],
            ['nama' => 'Surat Edaran', 'kode' => 'SE', 'deskripsi' => 'Edaran kebijakan atau informasi umum.'],
            ['nama' => 'Surat Permohonan', 'kode' => 'PRM', 'deskripsi' => 'Permohonan dari pihak internal atau eksternal.'],
            ['nama' => 'Nota Dinas', 'kode' => 'ND', 'deskripsi' => 'Komunikasi kedinasan internal.'],
        ] as $category) {
            LetterCategory::firstOrCreate(['kode' => $category['kode']], $category);
        }

        foreach ([
            ['nama' => 'Biasa', 'kode' => 'B', 'level_kerahasiaan' => 0],
            ['nama' => 'Penting', 'kode' => 'P', 'level_kerahasiaan' => 0],
            ['nama' => 'Segera', 'kode' => 'S', 'level_kerahasiaan' => 0],
            ['nama' => 'Rahasia', 'kode' => 'R', 'level_kerahasiaan' => 1],
        ] as $nature) {
            LetterNature::firstOrCreate(['kode' => $nature['kode']], $nature);
        }

        foreach ([
            ['nama' => 'Administrasi Umum', 'kode' => 'AU', 'masa_retensi' => 5],
            ['nama' => 'Akademik', 'kode' => 'AKD', 'masa_retensi' => 10],
            ['nama' => 'Keuangan', 'kode' => 'KEU', 'masa_retensi' => 10],
        ] as $classification) {
            ArchiveClassification::firstOrCreate(['kode' => $classification['kode']], $classification);
        }

        foreach ([
            ['judul' => 'Untuk Ditindaklanjuti', 'isi_instruksi' => 'Harap ditindaklanjuti sesuai ketentuan yang berlaku dan dilaporkan hasilnya.'],
            ['judul' => 'Untuk Dikoordinasikan', 'isi_instruksi' => 'Harap dikoordinasikan dengan unit terkait dan diproses lebih lanjut.'],
            ['judul' => 'Untuk Dihadiri', 'isi_instruksi' => 'Harap dihadiri dan diwakili jika berhalangan.'],
            ['judul' => 'Untuk Diarsipkan', 'isi_instruksi' => 'Harap disimpan dan diarsipkan sesuai klasifikasi arsip yang berlaku.'],
        ] as $instruction) {
            DispositionInstruction::firstOrCreate(['judul' => $instruction['judul']], $instruction);
        }

        $users = [
            ['name' => 'Super Admin', 'email' => 'superadmin@esurat.test', 'unit_id' => $rektorat->id, 'position' => 'Rektor', 'role' => 'super-admin'],
            ['name' => 'Admin Persuratan', 'email' => 'admin@esurat.test', 'unit_id' => $bau->id, 'position' => 'Kepala Biro Administrasi Umum', 'role' => 'admin-persuratan'],
            ['name' => 'Prof. Rektor', 'email' => 'rektor@esurat.test', 'unit_id' => $rektorat->id, 'position' => 'Rektor', 'role' => 'pimpinan-universitas'],
            ['name' => 'Dekan Fakultas Teknik', 'email' => 'dekanft@esurat.test', 'unit_id' => $ft->id, 'position' => 'Dekan Fakultas Teknik', 'role' => 'pimpinan-fakultas'],
            ['name' => 'Dosen Penerima', 'email' => 'dosen@esurat.test', 'unit_id' => $ft->id, 'position' => 'Dosen', 'role' => 'dosen'],
            ['name' => 'Tendik Penerima', 'email' => 'tendik@esurat.test', 'unit_id' => $bau->id, 'position' => 'Tenaga Kependidikan', 'role' => 'tendik'],
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            $position = $userData['position'];
            unset($userData['role'], $userData['position']);

            $user = User::firstOrCreate(['email' => $userData['email']], [
                ...$userData,
                'position_id' => Position::where('nama', $position)->first()->id,
                'password' => Hash::make('password'),
                'is_active' => true,
            ]);

            $user->syncRoles([$role]);
        }

        IncomingLetter::firstOrCreate(['nomor_agenda' => now()->format('Y').'/001'], [
            'nomor_surat' => '001/UND/EXT/V/2026',
            'tanggal_surat' => now()->subDays(2)->toDateString(),
            'tanggal_diterima' => now()->subDay()->toDateString(),
            'asal_surat' => 'Lembaga Layanan Pendidikan Tinggi',
            'perihal' => 'Undangan koordinasi pelaporan akademik',
            'ringkasan' => 'Undangan rapat koordinasi pelaporan akademik semester berjalan.',
            'sifat_surat_id' => LetterNature::where('kode', 'P')->first()->id,
            'status' => 'baru',
            'created_by' => User::where('email', 'admin@esurat.test')->first()->id,
        ]);
    }
}
