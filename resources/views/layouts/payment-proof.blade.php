<div data-proof-picker>
    <p class="text-sm font-medium">Bukti pembayaran</p>
    <p id="{{ $id }}-hint" class="mt-1 text-xs text-stone-600">Unggah gambar berukuran maksimal 2 MB.</p>
    <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
        <input id="{{ $id }}" class="peer sr-only" type="file" name="proof" accept="image/*"
            aria-describedby="{{ $id }}-hint" data-proof-input required>
        <label for="{{ $id }}" class="cursor-pointer rounded-lg border border-stone-300 bg-white px-3 py-2 text-neutral-900 peer-focus-visible:ring-2 peer-focus-visible:ring-red-700 peer-disabled:cursor-default peer-disabled:opacity-60">Pilih gambar</label>
        <span class="min-w-0 break-all text-stone-600" data-proof-name>Belum ada gambar yang dipilih.</span>
    </div>
</div>
