<?php

namespace App\Http\Controllers;

use App\Exceptions\TextOverflowException;
use App\Http\Requests\IssueRequest;
use App\Models\Certificate;
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
     * Halaman form penerbitan. Daftar kegiatan aktif dikirim sekali untuk dropdown pencarian.
     *
     * Rute: GET /terbitkan (Nama: issue.form)
     */
    public function create(): \Illuminate\Contracts\View\View
    {
        return view('issue.create', [
            'events' => Event::query()->aktif()
                ->get(['id', 'kode', 'judul', 'jenis', 'penerbit', 'bernomor', 'pakai_qr', 'program_default', 'aktif']),
        ]);
    }

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
     * Sertifikat terbit dengan nama yang sama pada kegiatan yang sama (PRD FM-11).
     * Hanya peringatan: operator tetap boleh menerbitkan.
     *
     * Rute: GET /api/nama-sama (Nama: api.same-name)
     */
    public function sameName(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'event_id' => ['required', 'integer'],
            'nama'     => ['required', 'string', 'max:100'],
        ]);

        $nama = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $validated['nama'])));

        return response()->json(Certificate::query()
            ->where('event_id', $validated['event_id'])
            ->where('status', 'terbit')
            ->whereRaw('LOWER(nama_peserta) = ?', [$nama])
            ->latest()->limit(5)
            ->get(['id', 'nomor_lengkap', 'tanggal_terbit'])
            ->map(fn ($c) => ['id' => $c->id, 'nomor' => $c->nomor_lengkap, 'tanggal' => $c->tanggal_terbit->translatedFormat('j F Y')]));
    }

    /**
     * Memeriksa apakah nama dan judul muat di sertifikat dengan renderer yang sama
     * seperti saat terbit, sebelum tombol Terbitkan ditekan (PRD PD-05, PD-06).
     *
     * Rute: POST /api/cek-teks (Nama: api.text-check)
     */
    public function checkText(IssueRequest $request, IssueService $issueService): JsonResponse
    {
        try {
            $issueService->preview($request->validated());
        } catch (TextOverflowException $e) {
            return response()->json(['muat' => false, 'field' => $e->fieldIn($request)]);
        } catch (\InvalidArgumentException) {
            return response()->json(['muat' => null]); // Data belum lengkap/valid; pesan lain sudah menangani.
        }

        return response()->json(['muat' => true]);
    }

    /**
     * Menjalankan penerbitan sertifikat resmi secara atomik dan mengunduh berkas PDF.
     *
     * Rute: POST /terbitkan (Nama: issue.store)
     */
    public function store(IssueRequest $request, IssueService $issueService): Response
    {
        // Tanpa akun cadangan buatan otomatis: penerbit harus pengguna nyata.
        $user = auth()->user();
        abort_unless($user, 401);

        try {
            $result = $issueService->issue($request->validated(), $user);
        } catch (\InvalidArgumentException $e) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'event_id' => [$e->getMessage()],
            ]);
        }

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
        try {
            $result = $issueService->preview($request->validated());
        } catch (\InvalidArgumentException $e) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'event_id' => [$e->getMessage()],
            ]);
        }

        return response($result['pdf'], 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$result['filename']}\"",
        ]);
    }
}
