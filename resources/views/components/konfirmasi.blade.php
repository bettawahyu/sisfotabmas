{{-- One shared dialog for every form or button that carries data-konfirmasi (see resources/js/app.js). --}}
<div x-data x-show="$store.konfirmasi.buka" style="display: none;"
     class="fixed inset-0 z-50 flex items-center justify-center p-4"
     @keydown.escape.window="$store.konfirmasi.batal()"
     x-effect="if ($store.konfirmasi.buka) $nextTick(() => $refs.batal.focus())">
    <div class="absolute inset-0 bg-gray-900/50" x-show="$store.konfirmasi.buka" x-transition.opacity @click="$store.konfirmasi.batal()"></div>
    <div role="alertdialog" aria-modal="true" aria-labelledby="konfirmasi-judul" aria-describedby="konfirmasi-pesan"
         x-show="$store.konfirmasi.buka" x-transition
         class="relative w-full max-w-md rounded-md bg-white p-6 shadow-xl">
        <div class="flex items-start gap-4">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
                  :class="$store.konfirmasi.bahaya ? 'bg-red-100 text-red-600' : 'bg-amber-100 text-amber-600'">
                <x-ikon nama="alert-triangle" class="h-5 w-5" />
            </span>
            <div>
                <h2 id="konfirmasi-judul" class="text-base font-semibold text-gray-800" x-text="$store.konfirmasi.bahaya ? 'Hapus data?' : 'Lanjutkan?'"></h2>
                <p id="konfirmasi-pesan" class="mt-1 text-sm text-gray-600" x-text="$store.konfirmasi.pesan"></p>
            </div>
        </div>
        <div class="mt-6 flex justify-end gap-3">
            <x-secondary-button x-ref="batal" x-on:click="$store.konfirmasi.batal()">Batal</x-secondary-button>
            <button type="button" @click="$store.konfirmasi.setuju()"
                    class="inline-flex items-center gap-2 rounded-md border border-transparent px-4 py-2 text-sm font-semibold text-white focus:outline-none focus:ring-2 focus:ring-offset-2"
                    :class="$store.konfirmasi.bahaya ? 'bg-red-600 hover:bg-red-500 focus:ring-red-500' : 'bg-indigo-600 hover:bg-indigo-500 focus:ring-indigo-500'"
                    x-text="$store.konfirmasi.bahaya ? 'Ya, hapus' : 'Ya, lanjutkan'"></button>
        </div>
    </div>
</div>
