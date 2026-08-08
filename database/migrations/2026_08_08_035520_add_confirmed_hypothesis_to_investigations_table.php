<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('investigations', function (Blueprint $table) {
            $table->foreignId('confirmed_hypothesis_id')
                ->nullable()
                ->after('status')
                ->constrained('hypotheses')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('investigations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('confirmed_hypothesis_id');
        });
    }
};
