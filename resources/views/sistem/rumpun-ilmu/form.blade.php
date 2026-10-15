@php use App\Models\RumpunIlmu; @endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $rumpunIlmu->exists ? 'Ubah Rumpun Ilmu' : 'Rumpun Ilmu Baru' }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <form method="POST" action="{{ $rumpunIlmu->exists ? route('sistem.rumpun-ilmu.update', $rumpunIlmu) : route('sistem.rumpun-ilmu.store') }}"
                      class="grid gap-4 sm:grid-cols-2" x-data="{ tingkat: @js(old('tingkat', $rumpunIlmu->tingkat)) }">
                    @csrf
                    @if ($rumpunIlmu->exists)
                        @method('PUT')
                    @endif
                    <div>
                        <x-input-label for="tingkat" value="Tingkat" ikon="layers" />
                        <x-select-input id="tingkat" name="tingkat" class="mt-1 block w-full" x-model="tingkat">
                            @foreach (RumpunIlmu::TINGKAT as $kode => $nama)
                                <option value="{{ $kode }}">{{ $nama }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="kode" value="Kode" ikon="hash" />
                        <x-text-input id="kode" name="kode" class="mt-1 block w-full" :value="old('kode', $rumpunIlmu->kode)" required />
                    </div>
                    <div class="sm:col-span-2" x-show="tingkat !== 'rumpun'">
                        <x-input-label for="induk_id" value="Induk" ikon="git-branch" />
                        <x-select-input id="induk_id" name="induk_id" class="mt-1 block w-full">
                            <option value="">-</option>
                            @foreach ($calonInduk as $tingkatInduk => $daftar)
                                @php($untuk = array_search($tingkatInduk, RumpunIlmu::TINGKAT_INDUK, true))
                                <optgroup label="{{ RumpunIlmu::TINGKAT[$tingkatInduk] }}" x-show="tingkat === @js($untuk)" :disabled="tingkat !== @js($untuk)">
                                    @foreach ($daftar as $induk)
                                        <option value="{{ $induk->id }}" @selected(old('induk_id', $rumpunIlmu->induk_id) == $induk->id)>{{ $induk->kode }} {{ $induk->nama }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </x-select-input>
                        <p class="mt-1 text-xs text-gray-500">Pohon berinduk rumpun; cabang berinduk pohon.</p>
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="nama" value="Nama" ikon="edit-3" />
                        <x-text-input id="nama" name="nama" class="mt-1 block w-full" :value="old('nama', $rumpunIlmu->nama)" required />
                    </div>
                    <div class="sm:col-span-2"><x-centang-aktif :nilai="$rumpunIlmu->is_active" /></div>
                    <div class="sm:col-span-2 flex items-center gap-3">
                        <x-primary-button ikon="save">Simpan</x-primary-button>
                        <a href="{{ route('sistem.rumpun-ilmu.index') }}" class="text-sm text-gray-600 hover:underline">Batal</a>
                    </div>
                </form>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
