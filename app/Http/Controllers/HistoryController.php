<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\User;
use Illuminate\Contracts\View\View;
use App\Services\HistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class HistoryController extends Controller
{
    /**
     * Daftar riwayat dengan saringan, ringkasan, grafik 6 bulan, dan aktivitas terbaru.
     * Operator hanya melihat sertifikat buatannya (Design System bagian 2).
     *
     * Rute: GET /riwayat (Nama: history.index)
     */
    public function index(Request $request): View
    {
        $me = $request->user() ?? User::query()->where('peran', 'admin')->first();
        $mine = fn ($q) => $me && ! $me->isAdmin() ? $q->where('dibuat_oleh', $me->id) : $q;

        $certificates = $mine(Certificate::query()->with(['event:id,kode,judul', 'creator:id,nama']))
            ->when($request->query('q'), fn ($q, $s) => $q->where(fn ($w) => $w->where('nama_peserta', 'like', "%{$s}%")->orWhere('nomor_lengkap', 'like', "%{$s}%")))
            ->when($request->query('jenis'), fn ($q, $v) => $q->whereIn('jenis', (array) $v))
            ->when($request->query('status'), fn ($q, $v) => $q->whereIn('status', (array) $v))
            ->when($request->query('dari'), fn ($q, $v) => $q->whereDate('tanggal_terbit', '>=', $v))
            ->when($request->query('sampai'), fn ($q, $v) => $q->whereDate('tanggal_terbit', '<=', $v))
            ->latest()->paginate(25)->withQueryString();

        $all = $mine(Certificate::query())->get(['jenis', 'status', 'tanggal_terbit']);
        $monthly = collect(range(5, 0))->mapWithKeys(function ($ago) use ($all) {
            $m = now()->startOfMonth()->subMonths($ago);

            return [$m->translatedFormat('M') => $all->filter(fn ($c) => $c->tanggal_terbit->isSameMonth($m))->count()];
        })->all();

        return view('history.index', [
            'certificates' => $certificates,
            'stats' => [
                'total' => $all->count(),
                'bulan_ini' => $monthly[array_key_last($monthly)],
                'seminar' => $all->where('jenis', 'seminar')->count(),
                'bootcamp' => $all->where('jenis', 'bootcamp')->count(),
                'batal' => $all->where('status', 'dibatalkan')->count(),
            ],
            'monthly' => $monthly,
            'activity' => AuditLog::with('user:id,nama')->latest('created_at')->limit(5)->get(),
        ]);
    }

    /**
     * Detail satu sertifikat. Operator hanya boleh membuka miliknya.
     *
     * Rute: GET /riwayat/{id} (Nama: history.show)
     */
    public function show(Request $request, int $id): View
    {
        $c = Certificate::with(['event', 'creator', 'canceller'])->findOrFail($id);
        $me = $request->user();
        abort_if($me && ! $me->isAdmin() && $c->dibuat_oleh !== $me->id, 404);

        return view('history.show', ['c' => $c]);
    }

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
