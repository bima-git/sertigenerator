<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'kode',
        'judul',
        'jenis',
        'penerbit',
        'bernomor',
        'pakai_qr',
        'program_default',
        'aktif',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'bernomor' => 'boolean',
        'pakai_qr' => 'boolean',
        'aktif' => 'boolean',
    ];

    /**
     * Relasi ke sertifikat yang diterbitkan untuk kegiatan ini.
     */
    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Scope untuk kegiatan yang aktif.
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('aktif', true);
    }

    /**
     * Scope untuk jenis kegiatan (seminar atau bootcamp).
     */
    public function scopeJenis(Builder $query, string $jenis): Builder
    {
        return $query->where('jenis', $jenis);
    }
}
