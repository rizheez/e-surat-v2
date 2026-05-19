<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('kode', 20)->unique();
            $table->foreignId('parent_id')->nullable()->constrained('units')->nullOnDelete();
            $table->timestamps();

            $table->index(['kode', 'parent_id']);
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->unsignedTinyInteger('level')->default(1);
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('letter_categories', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('kode', 20)->unique();
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        Schema::create('letter_natures', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50);
            $table->string('kode', 20)->unique();
            $table->unsignedTinyInteger('level_kerahasiaan')->default(0);
            $table->timestamps();
        });

        Schema::create('archive_classifications', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('kode', 20)->unique();
            $table->unsignedInteger('masa_retensi')->nullable();
            $table->timestamps();
        });

        Schema::create('disposition_instructions', function (Blueprint $table) {
            $table->id();
            $table->string('judul', 120);
            $table->text('isi_instruksi');
            $table->timestamps();
        });

        Schema::create('incoming_letters', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_agenda', 30)->unique();
            $table->string('nomor_surat', 100);
            $table->date('tanggal_surat');
            $table->date('tanggal_diterima');
            $table->string('asal_surat', 200);
            $table->string('perihal');
            $table->text('ringkasan')->nullable();
            $table->foreignId('sifat_surat_id')->constrained('letter_natures')->restrictOnDelete();
            $table->foreignId('kategori_surat_id')->constrained('letter_categories')->restrictOnDelete();
            $table->string('file_path', 500)->nullable();
            $table->string('status', 30)->default('baru');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['nomor_agenda', 'nomor_surat', 'status', 'tanggal_diterima'], 'incoming_lookup_idx');
            $table->index('asal_surat', 'incoming_origin_idx');
        });

        Schema::create('outgoing_letters', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat_keluar', 100)->unique();
            $table->date('tanggal_surat');
            $table->string('tujuan_surat', 200);
            $table->string('perihal');
            $table->text('ringkasan')->nullable();
            $table->foreignId('kategori_surat_id')->constrained('letter_categories')->restrictOnDelete();
            $table->string('file_path', 500)->nullable();
            $table->string('status', 30)->default('draft');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['status', 'tanggal_surat']);
        });

        Schema::create('dispositions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incoming_letter_id')->constrained('incoming_letters')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->text('instruksi');
            $table->text('catatan')->nullable();
            $table->date('batas_waktu')->nullable();
            $table->string('status', 30)->default('menunggu');
            $table->timestamp('tanggal_disposisi')->useCurrent();
            $table->timestamps();

            $table->index(['incoming_letter_id', 'sender_id', 'status', 'batas_waktu'], 'disposition_lookup_idx');
        });

        Schema::create('disposition_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disposition_id')->constrained('dispositions')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained('units')->nullOnDelete();
            $table->string('status', 30)->default('menunggu');
            $table->timestamp('tanggal_dibaca')->nullable();
            $table->timestamp('tanggal_selesai')->nullable();
            $table->timestamps();

            $table->unique(['disposition_id', 'recipient_id']);
            $table->index(['recipient_id', 'unit_id', 'status'], 'disposition_recipient_lookup_idx');
        });

        Schema::create('disposition_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('disposition_id')->constrained('dispositions')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->text('catatan');
            $table->string('file_path', 500)->nullable();
            $table->string('status', 30)->default('diproses');
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('log_name', 80);
            $table->text('description');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->index(['log_name', 'subject_type', 'subject_id'], 'activity_subject_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('disposition_followups');
        Schema::dropIfExists('disposition_recipients');
        Schema::dropIfExists('dispositions');
        Schema::dropIfExists('outgoing_letters');
        Schema::dropIfExists('incoming_letters');
        Schema::dropIfExists('disposition_instructions');
        Schema::dropIfExists('archive_classifications');
        Schema::dropIfExists('letter_natures');
        Schema::dropIfExists('letter_categories');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('units');
    }
};
