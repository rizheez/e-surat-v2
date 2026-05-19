<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table) {
            $table->foreignId('signatory_user_id')->nullable()->after('kategori_surat_id')->constrained('users')->nullOnDelete();
            $table->timestamp('approval_requested_at')->nullable()->after('signatory_user_id');
            $table->timestamp('approved_at')->nullable()->after('approval_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('signatory_user_id');
            $table->dropColumn([
                'approval_requested_at',
                'approved_at',
            ]);
        });
    }
};
