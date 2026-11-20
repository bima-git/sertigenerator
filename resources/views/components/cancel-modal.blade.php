{{-- Dialog pembatalan (Design System 10). Dibuka oleh tombol [data-open="cancel-modal"] dengan data-action dan data-nomor. --}}
<dialog id="cancel-modal" class="modal" aria-labelledby="cancel-title">
    <form method="POST" class="p-6 grid gap-5">
        @csrf
        <div class="flex items-start gap-4">
            <span class="grid place-items-center size-11 flex-none rounded-full bg-danger-soft text-danger text-xl"><i class="ph ph-prohibit"></i></span>
            <div>
                <h2 id="cancel-title" class="text-lg font-semibold">Batalkan sertifikat?</h2>
                <p class="mt-1 text-sm">Sertifikat <span class="num font-semibold text-fg" data-nomor></span> tidak dihapus tetapi ditandai tidak berlaku. Tulis alasan pembatalan.</p>
            </div>
        </div>
        <div class="field">
            <label for="alasan" class="label">Alasan pembatalan</label>
            <textarea id="alasan" name="alasan" class="input" required minlength="3" maxlength="255" aria-describedby="alasan-hint"></textarea>
            <p id="alasan-hint" class="hint">Minimal 3 karakter. Alasan tercatat di riwayat dan audit.</p>
            <p class="error" data-error hidden></p>
        </div>
        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" class="btn btn-ghost" data-close><span data-label>Kembali</span></button>
            <button class="btn btn-danger" data-loading-text="Membatalkan"><i class="ph ph-prohibit"></i><span data-label>Batalkan Sertifikat</span></button>
        </div>
    </form>
</dialog>
