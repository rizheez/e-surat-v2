<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_templates', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 120);
            $table->foreignId('kategori_surat_id')->constrained('letter_categories')->restrictOnDelete();
            $table->string('tujuan_surat', 200)->nullable();
            $table->string('perihal');
            $table->text('ringkasan')->nullable();
            $table->string('lampiran_text', 150)->nullable();
            $table->string('kepada_text', 200)->nullable();
            $table->string('lokasi_tujuan', 150)->nullable();
            $table->text('salam_pembuka')->nullable();
            $table->longText('isi_surat');
            $table->longText('lampiran_detail')->nullable();
            $table->longText('penutup_text')->nullable();
            $table->longText('tembusan_text')->nullable();
            $table->timestamps();

            $table->index(['kategori_surat_id', 'nama']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_templates');
    }
};
