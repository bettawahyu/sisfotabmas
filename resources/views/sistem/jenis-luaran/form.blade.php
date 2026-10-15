@php use App\Models\JenisLuaran; @endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $jenis->exists ? 'Ubah Jenis Luaran' : 'Jenis Luaran Baru' }}</h2>
    </x-slot>

    @php($kodeTerkunci = $jenis->exists && $jenis->luaran()->exists())
    <div class="py-6">
        <div class="max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <form method="POST" action="{{ $jenis->exists ? route('sistem.jenis-luaran.update', $jenis) : route('sistem.jenis-luaran.store') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    @if ($jenis->exists)
                        @method('PUT')
                    @endif
                    <div>
                        <x-input-label for="kode" value="Kode (huruf kecil, angka, garis bawah)" ikon="hash" />
                        <x-text-input id="kode" name="kode" class="mt-1 block w-full" :value="old('kode', $jenis->kode)" required :readonly="$kodeTerkunci" />
                        @if ($kodeTerkunci)
                            <p class="mt-1 text-xs text-gray-500">Kode tidak bisa diubah karena sudah dipakai proposal.</p>
                        @endif
                    </div>
                    <div>
                        <x-input-label for="urutan" value="Urutan tampil" ikon="list" />
                        <x-text-input id="urutan" name="urutan" type="number" min="0" class="mt-1 block w-full" :value="old('urutan', $jenis->urutan)" required />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="nama" value="Nama" ikon="edit-3" />
                        <x-text-input id="nama" name="nama" class="mt-1 block w-full" :value="old('nama', $jenis->nama)" required />
                    </div>
                    <div>
                        <x-input-label for="berlaku_untuk" value="Berlaku untuk" ikon="tag" />
                        <x-select-input id="berlaku_untuk" name="berlaku_untuk" class="mt-1 block w-full">
                            @foreach (JenisLuaran::BERLAKU as $kode => $nama)
                                <option value="{{ $kode }}" @selected(old('berlaku_untuk', $jenis->berlaku_untuk) === $kode)>{{ $nama }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="tautan_karya" value="Bisa ditautkan ke" ikon="link" />
                        <x-select-input id="tautan_karya" name="tautan_karya" class="mt-1 block w-full">
                            <option value="">Tidak ditautkan</option>
                            @foreach (JenisLuaran::NAMA_TAUTAN as $kode => $nama)
                                <option value="{{ $kode }}" @selected(old('tautan_karya', $jenis->tautan_karya) === $kode)>{{ $nama }}</option>
                            @endforeach
                        </x-select-input>
                        <p class="mt-1 text-xs text-gray-500">Saat luaran tercapai, ketua bisa menautkannya ke entri di menu Publikasi &amp; KI.</p>
                    </div>
                    <div class="sm:col-span-2"><x-centang-aktif :nilai="$jenis->is_active" /></div>
                    <div class="sm:col-span-2 flex items-center gap-3">
                        <x-primary-button ikon="save">Simpan</x-primary-button>
                        <a href="{{ route('sistem.jenis-luaran.index') }}" class="text-sm text-gray-600 hover:underline">Batal</a>
                    </div>
                </form>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
