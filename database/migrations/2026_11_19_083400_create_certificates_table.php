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
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->enum('jenis', ['seminar', 'bootcamp']);
            $table->unsignedSmallInteger('seri')->nullable();
            $table->unsignedSmallInteger('urut')->nullable();
            $table->unsignedSmallInteger('tahun');
            $table->string('nomor_lengkap', 120)->nullable()->unique();
            $table->string('nama_peserta', 100)->index('idx_nama');
            $table->date('tanggal_terbit')->index('idx_tanggal');
            $table->json('data_cetak'); // Salinan seluruh teks yang dicetak
            $table->string('versi_layout', 20);
            $table->enum('status', ['terbit', 'dibatalkan'])->default('terbit');
            $table->string('alasan_batal', 255)->nullable();
            $table->foreignId('dibatalkan_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('dibatalkan_pada')->nullable();
            $table->foreignId('dibuat_oleh')->constrained('users');
            $table->timestamps();

            $table->unique(['event_id', 'seri', 'urut', 'tahun'], 'uq_event_seri_urut_tahun');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
