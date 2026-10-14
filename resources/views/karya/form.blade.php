@php use App\Services\AlurKarya; @endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $karya->exists ? 'Ubah' : 'Tambah' }} {{ AlurKarya::LABEL[$jenis] }}</h2>
        <p class="mt-1 text-sm text-gray-600"><a href="{{ route("karya.{$jenis}.index") }}" class="text-indigo-700 hover:underline">Kembali ke daftar</a></p>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <x-pesan />
            <form method="POST" action="{{ $karya->exists ? route("karya.{$jenis}.update", $karya) : route("karya.{$jenis}.store") }}" class="space-y-6">
                @csrf
                @if ($karya->exists)
                    @method('PUT')
                    <p class="text-sm text-gray-600">Setelah disimpan, data kembali menunggu verifikasi kaprodi.</p>
                @endif
                <x-kartu>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @include("karya.isian.{$jenis}")
                    </div>
                </x-kartu>

                <x-kartu :judul="['publikasi' => 'Penulis', 'hki' => 'Pencipta', 'paten' => 'Inventor'][$jenis]">
                    <p class="mb-4 text-sm text-gray-600">Isi sesuai urutan. Pilih dosen bila orangnya dosen di sini; untuk pihak luar cukup tulis nama dan afiliasinya. Nama Anda harus tercantum.</p>
                    <div class="space-y-3">
                        @foreach ($kontributor as $i => $k)
                            <div class="grid gap-3 sm:grid-cols-12 items-center">
                                <span class="sm:col-span-1 text-sm text-gray-500">{{ $i + 1 }}.</span>
                                <x-select-input name="kontributor[{{ $i }}][dosen_id]" class="sm:col-span-4" aria-label="Dosen urutan {{ $i + 1 }}">
                                    <option value="">Pihak luar</option>
                                    @foreach ($dosen as $d)
                                        <option value="{{ $d->id }}" @selected((string) old("kontributor.$i.dosen_id", $k['dosen_id'] ?? '') === (string) $d->id)>{{ $d->namaLengkap() }}</option>
                                    @endforeach
                                </x-select-input>
                                <x-text-input name="kontributor[{{ $i }}][nama]" placeholder="Nama (pihak luar)" class="sm:col-span-3" :value="old('kontributor.'.$i.'.nama', $k['nama'] ?? null)" />
                                <x-text-input name="kontributor[{{ $i }}][afiliasi]" placeholder="Afiliasi" :class="$jenis === 'publikasi' ? 'sm:col-span-2' : 'sm:col-span-4'" :value="old('kontributor.'.$i.'.afiliasi', $k['afiliasi'] ?? null)" />
                                @if ($jenis === 'publikasi')
                                    <label class="sm:col-span-2 flex items-center gap-2 text-sm text-gray-700">
                                        <input type="radio" name="korespondensi" value="{{ $i }}" @checked((string) old('korespondensi', ($k['is_korespondensi'] ?? false) ? $i : '') === (string) $i) class="border-gray-300">
                                        Korespondensi
                                    </label>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-kartu>

                <x-primary-button>Simpan</x-primary-button>
            </form>
        </div>
    </div>
</x-app-layout>
