@php use App\Enums\JenisKegiatan; @endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $bidangFokus->exists ? 'Ubah Bidang Fokus' : 'Bidang Fokus Baru' }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <form method="POST" action="{{ $bidangFokus->exists ? route('sistem.bidang-fokus.update', $bidangFokus) : route('sistem.bidang-fokus.store') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    @if ($bidangFokus->exists)
                        @method('PUT')
                    @endif
                    <div>
                        <x-input-label for="jenis_kegiatan" value="Jenis kegiatan" ikon="tag" />
                        <x-select-input id="jenis_kegiatan" name="jenis_kegiatan" class="mt-1 block w-full">
                            @foreach (JenisKegiatan::cases() as $jenis)
                                <option value="{{ $jenis->value }}" @selected(old('jenis_kegiatan', $bidangFokus->jenis_kegiatan?->value) === $jenis->value)>{{ $jenis->label() }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="kode" value="Kode" ikon="hash" />
                        <x-text-input id="kode" name="kode" class="mt-1 block w-full" :value="old('kode', $bidangFokus->kode)" required />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="nama" value="Nama" ikon="edit-3" />
                        <x-text-input id="nama" name="nama" class="mt-1 block w-full" :value="old('nama', $bidangFokus->nama)" required />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="induk_id" value="Induk (opsional, untuk subbidang)" ikon="git-branch" />
                        <x-select-input id="induk_id" name="induk_id" class="mt-1 block w-full">
                            <option value="">- Bidang utama -</option>
                            @foreach ($calonInduk as $induk)
                                <option value="{{ $induk->id }}" @selected(old('induk_id', $bidangFokus->induk_id) == $induk->id)>{{ $induk->jenis_kegiatan->label() }}: {{ $induk->kode }} {{ $induk->nama }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div class="sm:col-span-2"><x-centang-aktif :nilai="$bidangFokus->is_active" /></div>
                    <div class="sm:col-span-2 flex items-center gap-3">
                        <x-primary-button ikon="save">Simpan</x-primary-button>
                        <a href="{{ route('sistem.bidang-fokus.index') }}" class="text-sm text-gray-600 hover:underline">Batal</a>
                    </div>
                </form>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
