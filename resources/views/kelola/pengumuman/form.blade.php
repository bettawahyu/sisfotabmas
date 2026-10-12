@php
    use App\Models\Pengumuman;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $pengumuman->exists ? 'Ubah Pengumuman' : 'Pengumuman Baru' }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <p class="mb-6 text-sm text-gray-600">Pengumuman dikirim sebagai notifikasi ke pembacanya sekali, saat waktu terbitnya tiba.</p>
                <form method="POST" enctype="multipart/form-data" action="{{ $pengumuman->exists ? route('kelola.pengumuman.update', $pengumuman) : route('kelola.pengumuman.store') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    @if ($pengumuman->exists)
                        @method('PUT')
                    @endif
                    <div class="sm:col-span-2">
                        <x-input-label for="judul" value="Judul" />
                        <x-text-input id="judul" name="judul" class="mt-1 block w-full" :value="old('judul', $pengumuman->judul)" required />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="isi" value="Isi" />
                        <textarea id="isi" name="isi" rows="8" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('isi', $pengumuman->isi) }}</textarea>
                    </div>
                    <div>
                        <x-input-label for="kategori" value="Kategori" />
                        <x-select-input id="kategori" name="kategori" class="mt-1 block w-full">
                            @foreach (Pengumuman::KATEGORI as $kode => $label)
                                <option value="{{ $kode }}" @selected(old('kategori', $pengumuman->kategori) === $kode)>{{ $label }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="target_peran" value="Ditujukan untuk" />
                        <x-select-input id="target_peran" name="target_peran" class="mt-1 block w-full">
                            @foreach ($peran as $kode => $label)
                                <option value="{{ $kode }}" @selected(old('target_peran', $pengumuman->target_peran) === $kode)>{{ $label }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="periode_id" value="Periode hibah terkait (opsional)" />
                        <x-select-input id="periode_id" name="periode_id" class="mt-1 block w-full">
                            <option value="">Tidak terkait periode</option>
                            @foreach ($periode as $p)
                                <option value="{{ $p->id }}" @selected((string) old('periode_id', $pengumuman->periode_id) === (string) $p->id)>{{ $p->nama }} ({{ $p->tahun_anggaran }})</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="terbit_at" value="Terbit" />
                        <x-text-input id="terbit_at" name="terbit_at" type="datetime-local" class="mt-1 block w-full" :value="old('terbit_at', $pengumuman->terbit_at?->format('Y-m-d\TH:i'))" required />
                    </div>
                    <div>
                        <x-input-label for="berakhir_at" value="Berakhir (opsional)" />
                        <x-text-input id="berakhir_at" name="berakhir_at" type="datetime-local" class="mt-1 block w-full" :value="old('berakhir_at', $pengumuman->berakhir_at?->format('Y-m-d\TH:i'))" />
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700 sm:col-span-2">
                        <input type="hidden" name="is_pinned" value="0">
                        <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned', $pengumuman->is_pinned)) class="rounded border-gray-300">
                        Sematkan di urutan teratas
                    </label>
                    <div class="sm:col-span-2">
                        <x-input-label for="lampiran" value="Lampiran PDF (opsional, maksimal 10 MB)" />
                        @if ($pengumuman->lampiran)
                            <p class="mt-1 text-sm text-gray-700">Saat ini: <a class="text-indigo-700 underline" href="{{ route('pengumuman.lampiran', $pengumuman) }}">{{ $pengumuman->lampiran->nama_asli }}</a></p>
                            <label class="mt-1 flex items-center gap-2 text-sm text-gray-700">
                                <input type="hidden" name="hapus_lampiran" value="0">
                                <input type="checkbox" name="hapus_lampiran" value="1" class="rounded border-gray-300">
                                Hapus lampiran ini
                            </label>
                        @endif
                        <input id="lampiran" type="file" name="lampiran" accept="application/pdf" class="mt-2 text-sm">
                    </div>
                    <div class="sm:col-span-2"><x-primary-button>Simpan</x-primary-button></div>
                </form>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
