<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dispositions', function (Blueprint $table) {
            $table->foreignId('parent_disposition_id')
                ->nullable()
                ->after('incoming_letter_id')
                ->constrained('dispositions')
                ->nullOnDelete();

            $table->index(['parent_disposition_id', 'sender_id', 'status'], 'disposition_parent_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::table('dispositions', function (Blueprint $table) {
            $table->dropIndex('disposition_parent_lookup_idx');
            $table->dropConstrainedForeignId('parent_disposition_id');
        });
    }
};
