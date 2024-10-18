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
        Schema::create('pembayarans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penghuni_id')->constrained('penghunis')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreignId('iuran_id')->constrained('iurans')->cascadeOnUpdate()->cascadeOnDelete();
            $table->integer('biaya_pembayaran');
            $table->date('tanggal_pembayaran')->nullable();
            $table->string('status_pembayaran')->default('Belum Lunas');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembayarans');
    }
};
