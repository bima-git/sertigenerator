<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10);
            $table->string('judul', 500);
            $table->enum('jenis', ['seminar', 'bootcamp']);
            $table->string('penerbit', 50)->default('Xcodetraining');
            $table->boolean('bernomor')->default(true);
            $table->boolean('pakai_qr')->default(true);
            $table->string('program_default', 200)->nullable();
            $table->boolean('aktif')->default(true);

            $table->timestamps();

            $table->index('kode', 'idx_kode');
            $table->index(
                ['jenis', 'aktif'],
                'idx_jenis_aktif'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};