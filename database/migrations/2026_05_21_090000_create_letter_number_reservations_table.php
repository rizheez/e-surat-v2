<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_number_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat', 100)->unique();
            $table->date('tanggal_surat');
            $table->foreignId('kategori_surat_id')->constrained('letter_categories')->restrictOnDelete();
            $table->string('jenis_dokumen', 80)->nullable();
            $table->string('perihal');
            $table->string('tujuan_surat', 200)->nullable();
            $table->text('catatan')->nullable();
            $table->string('status', 20)->default('reserved');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('used_by_outgoing_letter_id')->nullable()->constrained('outgoing_letters')->nullOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'tanggal_surat']);
            $table->index(['kategori_surat_id', 'created_by']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_number_reservations');
    }
};
