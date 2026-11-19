<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'event_id',
        'jenis',
        'seri',
        'urut',
        'tahun',
        'nomor_lengkap',
        'nama_peserta',
        'tanggal_terbit',
        'data_cetak',
        'versi_layout',
        'status',
        'alasan_batal',
        'dibatalkan_oleh',
        'dibatalkan_pada',
        'dibuat_oleh',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'seri' => 'integer',
        'urut' => 'integer',
        'tahun' => 'integer',
        'tanggal_terbit' => 'date',
        'data_cetak' => 'array',
        'dibatalkan_pada' => 'datetime',
    ];

    /**
     * Relasi ke master kegiatan.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Pengguna yang menerbitkan sertifikat ini.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibuat_oleh');
    }

    /**
     * Admin yang membatalkan sertifikat ini (bila dibatalkan).
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dibatalkan_oleh');
    }

    /**
     * Apakah sertifikat masih berstatus sah / terbit.
     */
    public function isTerbit(): bool
    {
        return $this->status === 'terbit';
    }

    /**
     * Apakah sertifikat telah dibatalkan.
     */
    public function isDibatalkan(): bool
    {
        return $this->status === 'dibatalkan';
    }

    /**
     * Scope sertifikat berstatus terbit.
     */
    public function scopeTerbit(Builder $query): Builder
    {
        return $query->where('status', 'terbit');
    }

    /**
     * Scope sertifikat berstatus dibatalkan.
     */
    public function scopeDibatalkan(Builder $query): Builder
    {
        return $query->where('status', 'dibatalkan');
    }
}
