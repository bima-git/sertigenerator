<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventRequest;
use App\Models\Event;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    public function __construct(
        protected AuditLogger $auditLogger
    ) {}

    /**
     * Tampilkan daftar kegiatan dengan pencarian dan filter jenis/status.
     * PRD KG-07.
     */
    public function index(Request $request): View
    {
        $events = Event::query()
            ->when($request->query('jenis'), fn ($q, $j) => $q->where('jenis', $j))
            ->when($request->query('q'), function ($q, $s) {
                $q->where(function ($w) use ($s) {
                    $w->where('kode', 'like', "%{$s}%")
                      ->orWhere('judul', 'like', "%{$s}%");
                });
            })
            ->orderByDesc('aktif')
            ->orderByRaw('CAST(kode AS UNSIGNED)')
            ->orderBy('kode')
            ->get();

        return view('admin.events.index', [
            'events' => $events,
        ]);
    }

    /**
     * Tampilkan formulir tambah kegiatan baru.
     */
    public function create(): View
    {
        return view('admin.events.form', [
            'event' => null,
        ]);
    }

    /**
     * Simpan kegiatan baru ke database dan catat log audit.
     * PRD KG-01, AD-01.
     */
    public function store(EventRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $event = Event::create($validated);

        $this->auditLogger->log(
            $request->user(),
            'ubah_event',
            $event->id,
            [
                'tindakan' => 'tambah',
                'kode' => $event->kode,
                'judul' => $event->judul,
                'jenis' => $event->jenis,
                'penerbit' => $event->penerbit,
            ]
        );

        return redirect()
            ->route('admin.events.index')
            ->with('success', "Kegiatan \"{$event->kode} - {$event->judul}\" berhasil ditambahkan.");
    }

    /**
     * Tampilkan formulir ubah data kegiatan.
     */
    public function edit(int $id): View
    {
        $event = Event::findOrFail($id);

        return view('admin.events.form', [
            'event' => $event,
        ]);
    }

    /**
     * Perbarui data kegiatan dan catat log audit.
     * PRD KG-02, KG-05, AD-01.
     */
    public function update(EventRequest $request, int $id): RedirectResponse
    {
        $event = Event::findOrFail($id);

        $validated = $request->validated();
        $event->update($validated);

        $this->auditLogger->log(
            $request->user(),
            'ubah_event',
            $event->id,
            [
                'tindakan' => 'ubah',
                'kode' => $event->kode,
                'judul' => $event->judul,
                'jenis' => $event->jenis,
                'penerbit' => $event->penerbit,
            ]
        );

        return redirect()
            ->route('admin.events.index')
            ->with('success', "Kegiatan \"{$event->kode}\" berhasil diperbarui.");
    }

    /**
     * Alihkan status aktif/nonaktif kegiatan dan catat log audit.
     * PRD KG-02, KG-03, AD-01.
     */
    public function toggle(Request $request, int $id): RedirectResponse
    {
        $event = Event::findOrFail($id);
        $event->aktif = ! $event->aktif;
        $event->save();

        $statusLabel = $event->aktif ? 'diaktifkan' : 'dinonaktifkan';

        $this->auditLogger->log(
            $request->user(),
            'ubah_event',
            $event->id,
            [
                'tindakan' => $event->aktif ? 'aktifkan' : 'nonaktifkan',
                'kode' => $event->kode,
                'judul' => $event->judul,
                'aktif' => $event->aktif,
            ]
        );

        return redirect()
            ->route('admin.events.index')
            ->with('success', "Kegiatan \"{$event->kode}\" berhasil {$statusLabel}.");
    }
}
