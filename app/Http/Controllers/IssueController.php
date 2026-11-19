<?php

namespace App\Http\Controllers;

use App\Http\Requests\IssueRequest;
use App\Models\Event;
use App\Models\User;
use App\Services\IssueService;
use App\Services\NumberAllocator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class IssueController extends Controller
{
    /**
     * Mengembalikan daftar kegiatan aktif untuk dropdown pilihan pada form penerbitan.
     * Mendukung filter jenis (seminar/bootcamp) dan pencarian query (q).
     *
     * Rute: GET /api/kegiatan (Nama: api.events)
     */
    public function events(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'jenis' => ['nullable', 'string', 'in:seminar,bootcamp'],
            'q'     => ['nullable', 'string', 'max:100'],
        ]);

        $query = Event::query()->aktif();

        if (! empty($validated['jenis'])) {
            $query->jenis($validated['jenis']);
        }

        if (! empty($validated['q'])) {
            $keyword = trim($validated['q']);
            $query->where(function ($subQuery) use ($keyword) {
                $subQuery->where('kode', 'like', "%{$keyword}%")
                    ->orWhere('judul', 'like', "%{$keyword}%");
            });
        }

        $events = $query->select([
            'id',
            'kode',
            'judul',
            'jenis',
            'penerbit',
            'bernomor',
            'pakai_qr',
            'program_default',
        ])->get();

        return response()->json($events);
    }

    /**
     * Menghasilkan saran nomor urut dan pratinjau string nomor sertifikat lengkap secara dinamis.
     *
     * Rute: GET /api/nomor (Nama: api.number)
     */
    public function suggest(Request $request, NumberAllocator $allocator): JsonResponse
    {
        $validated = $request->validate([
            'event_id'       => ['required', 'integer', 'exists:events,id'],
            'seri'           => ['nullable', 'integer', 'min:1', 'max:999'],
            'tanggal_terbit' => ['nullable', 'date'],
            'urut'           => ['nullable', 'integer', 'min:1', 'max:999'],
        ]);

        $event = Event::query()->findOrFail($validated['event_id']);
        $seri = isset($validated['seri']) ? (int) $validated['seri'] : 1;
        $tanggalTerbit = $validated['tanggal_terbit'] ?? null;
        $manualUrut = isset($validated['urut']) ? (int) $validated['urut'] : null;

        $suggestion = $allocator->suggest(
            $event,
            $seri,
            $tanggalTerbit,
            $manualUrut
        );

        return response()->json($suggestion);
    }

    /**
     * Menjalankan penerbitan sertifikat resmi secara atomik dan mengunduh berkas PDF.
     *
     * Rute: POST /terbitkan (Nama: issue.store)
     */
    public function store(IssueRequest $request, IssueService $issueService): Response
    {
        $user = $request->user() ?? User::query()->first() ?? User::create([
            'name'     => 'Operator Cadangan',
            'email'    => 'operator@xcode.test',
            'password' => bcrypt('password'),
        ]);

        $result = $issueService->issue($request->validated(), $user);

        return response($result['pdf'], 200, [
            'Content-Type'         => 'application/pdf',
            'Content-Disposition'  => "attachment; filename=\"{$result['filename']}\"",
            'X-Certificate-Number' => $result['nomor'] ?? '',
        ]);
    }

    /**
     * Membuat pratinjau PDF ber-watermark PRATINJAU tanpa menyimpan ke database.
     *
     * Rute: POST /pratinjau (Nama: issue.preview)
     */
    public function preview(IssueRequest $request, IssueService $issueService): Response
    {
        $result = $issueService->preview($request->validated());

        return response($result['pdf'], 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$result['filename']}\"",
        ]);
    }
}
