<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Unit Pimpinan</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <p class="mb-4 text-sm text-gray-600">Kaprodi hanya mengesahkan proposal dosen di prodinya, dan dekan untuk semua prodi di fakultasnya. Pimpinan yang belum ditautkan ke unit melihat semua proposal.</p>
                <table class="min-w-full text-sm" data-tabel-data>
                    <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Pimpinan</th><th class="py-2 font-medium">Unit</th></tr></thead>
                    <tbody>
                        @forelse ($pimpinan as $u)
                            @php($pivot = $u->roles->firstWhere('kode', \App\Models\Role::PIMPINAN_UNIT)->pivot)
                            @php($dipilih = $pivot->prodi_id ? 'prodi:'.$pivot->prodi_id : ($pivot->fakultas_id ? 'fakultas:'.$pivot->fakultas_id : ''))
                            <tr class="border-b border-gray-100 last:border-0">
                                <td class="py-2 pe-4">{{ $u->name }}<div class="text-xs text-gray-500">{{ $u->email }}</div>
                                    @if ($dipilih === '')<div class="text-xs text-amber-700">Belum ditautkan ke unit</div>@endif</td>
                                <td class="py-2">
                                    <form method="POST" action="{{ route('admin.pimpinan-unit.update', $u) }}" class="flex flex-wrap gap-3">
                                        @csrf
                                        @method('PUT')
                                        <x-select-input name="unit" class="flex-1">
                                            <option value="">Semua unit</option>
                                            @foreach ($fakultas as $f)
                                                <optgroup label="{{ $f->nama }}">
                                                    <option value="fakultas:{{ $f->id }}" @selected($dipilih === 'fakultas:'.$f->id)>Dekan {{ $f->nama }} (semua prodi)</option>
                                                    @foreach ($f->programStudi as $prodi)
                                                        <option value="prodi:{{ $prodi->id }}" @selected($dipilih === 'prodi:'.$prodi->id)>Kaprodi {{ $prodi->nama }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </x-select-input>
                                        <x-secondary-button type="submit" ikon="save">Simpan</x-secondary-button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="py-4 text-gray-600">Belum ada akun berperan pimpinan unit.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
