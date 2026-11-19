<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Mencatat peristiwa penting ke tabel audit_logs.
     *
     * @param User|int|null $user
     * @param string $aksi
     * @param int|null $targetId
     * @param array<string, mixed>|null $detail
     * @param string|null $ip
     * @return AuditLog
     */
    public function log(
        User|int|null $user,
        string $aksi,
        ?int $targetId = null,
        ?array $detail = null,
        ?string $ip = null
    ): AuditLog {
        $userId = $user instanceof User ? $user->id : $user;
        $ip = $ip ?? Request::ip();

        return AuditLog::create([
            'user_id' => $userId,
            'aksi' => $aksi,
            'target_id' => $targetId,
            'detail' => $detail,
            'ip' => $ip,
            'created_at' => now(),
        ]);
    }
}
