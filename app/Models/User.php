<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'email',
        'nama',
        'password',
        'peran',
        'aktif',
        'gagal_login',
        'terkunci_sampai',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'aktif' => 'boolean',
            'terkunci_sampai' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->peran === 'admin';
    }

    /** Email disimpan huruf kecil tanpa spasi supaya login dan reset tidak peka huruf besar. */
    protected function email(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: fn (?string $value) => $value === null || trim($value) === '' ? null : mb_strtolower(trim($value)),
        );
    }

    /** Email reset kata sandi berbahasa Indonesia, tautannya ke halaman /reset-sandi. */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new \App\Notifications\ResetKataSandi($token));
    }
}
