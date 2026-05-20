<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table) {
            $table->string('verification_token', 80)->nullable()->unique()->after('approval_note');
            $table->timestamp('verification_token_generated_at')->nullable()->after('verification_token');
        });
    }

    public function down(): void
    {
        Schema::table('outgoing_letters', function (Blueprint $table) {
            $table->dropColumn([
                'verification_token',
                'verification_token_generated_at',
            ]);
        });
    }
};
