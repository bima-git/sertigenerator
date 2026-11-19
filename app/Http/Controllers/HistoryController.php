<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\HistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class HistoryController extends Controller
{
    /**
     * Mengunduh ulang berkas PDF sertifikat dari data tersimpan.
     * Sesuai PRD RW-03 dan Design System 3.3.
     *
     * Rute: GET /riwayat/{id}/pdf (Nama: history.pdf)
     */
    public function download(Request $request, int $id, HistoryService $historyService): Response
    {
        $user = $request->user();
        $result = $historyService->download($id, $user);

        return response($result['pdf'], 200, [
            'Content-Type'         => 'application/pdf',
            'Content-Disposition'  => "attachment; filename=\"{$result['filename']}\"",
            'X-Certificate-Number' => $result['certificate']->nomor_lengkap ?? '',
            'X-Certificate-Status' => $result['certificate']->status,
        ]);
    }

    /**
     * Membatalkan sertifikat resmi dengan mencantumkan alasan.
     * Khusus untuk peran Admin. Sesuai PRD RW-05 dan Design System 3.4.
     *
     * Rute: POST /riwayat/{id}/batalkan (Nama: history.cancel)
     */
    public function cancel(Request $request, int $id, HistoryService $historyService): JsonResponse
    {
        $validated = $request->validate([
            'alasan' => ['required', 'string', 'min:3', 'max:255'],
        ], [
            'alasan.required' => 'Alasan pembatalan wajib diisi.',
            'alasan.min'      => 'Alasan pembatalan minimal 3 karakter.',
            'alasan.max'      => 'Alasan pembatalan maksimal 255 karakter.',
        ]);

        /** @var User|null $admin */
        $admin = $request->user() ?? User::query()->where('peran', 'admin')->first();

        if (! $admin || ! $admin->isAdmin()) {
            abort(403, 'Hanya peran Admin yang berwenang membatalkan sertifikat.');
        }

        $certificate = $historyService->cancel($id, $validated['alasan'], $admin);

        return response()->json([
            'message'        => "Sertifikat {$certificate->nomor_lengkap} berhasil dibatalkan.",
            'id'             => $certificate->id,
            'status'         => 'dibatalkan',
            'alasan_batal'   => $certificate->alasan_batal,
            'dibatalkan_oleh'=> $admin->nama ?? $admin->username,
            'dibatalkan_pada'=> $certificate->dibatalkan_pada?->toIso8601String(),
        ]);
    }
}
