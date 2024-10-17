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
        Schema::create('history_rumahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rumah_id')->constrained('rumahs')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('penghuni_id')->constrained('penghunis')->cascadeOnUpdate()->cascadeOnDelete();
            $table->date('tanggal_mulai_huni');
            $table->date('tanggal_akhir_huni');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('history_rumahs');
    }
};
