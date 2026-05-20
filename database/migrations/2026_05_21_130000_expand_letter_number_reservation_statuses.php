<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_number_reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('used_by_outgoing_letter_id');
            $table->foreignId('used_by_outgoing_letter_id')->nullable()->after('created_by')->constrained('outgoing_letters')->nullOnDelete();
        });

        DB::table('letter_number_reservations')
            ->where('status', 'used')
            ->whereNull('used_at')
            ->update(['used_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('letter_number_reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('used_by_outgoing_letter_id');
            $table->foreignId('used_by_outgoing_letter_id')->nullable()->after('created_by')->constrained('outgoing_letters')->nullOnDelete();
        });
    }
};
