<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table) {
            $table->string('content_mode', 20)->default('upload')->after('kategori_surat_id');
            $table->string('lampiran_text', 150)->nullable()->after('content_mode');
            $table->string('kepada_text', 200)->nullable()->after('lampiran_text');
            $table->string('lokasi_tujuan', 150)->nullable()->after('kepada_text');
            $table->text('salam_pembuka')->nullable()->after('lokasi_tujuan');
            $table->longText('isi_surat')->nullable()->after('salam_pembuka');
            $table->text('lampiran_detail')->nullable()->after('isi_surat');
            $table->text('penutup_text')->nullable()->after('lampiran_detail');
            $table->string('penandatangan_jabatan', 200)->nullable()->after('penutup_text');
            $table->string('penandatangan_nama', 200)->nullable()->after('penandatangan_jabatan');
            $table->text('tembusan_text')->nullable()->after('penandatangan_nama');
        });
    }

    public function down(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table) {
            $table->dropColumn([
                'content_mode',
                'lampiran_text',
                'kepada_text',
                'lokasi_tujuan',
                'salam_pembuka',
                'isi_surat',
                'lampiran_detail',
                'penutup_text',
                'penandatangan_jabatan',
                'penandatangan_nama',
                'tembusan_text',
            ]);
        });
    }
};
