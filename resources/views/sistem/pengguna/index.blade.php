<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pengguna dan Peran</h2>
            <div class="flex items-center gap-2">
                <x-tombol-tautan href="{{ route('sistem.impor.create', 'pengguna') }}" ikon="upload" varian="kedua">Impor Excel</x-tombol-tautan>
                <x-tombol-tautan href="{{ route('sistem.pengguna.create') }}" ikon="user-plus">Tambah pengguna</x-tombol-tautan>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <form method="GET" action="{{ route('sistem.pengguna.index') }}" class="mb-4 flex flex-wrap gap-3">
                    <x-select-input name="peran">
                        <option value="">Semua peran</option>
                        @foreach (\App\Models\Role::ALL as $kode => $nama)
                            <option value="{{ $kode }}" @selected($peran === $kode)>{{ $nama }}</option>
                        @endforeach
                    </x-select-input>
                    <x-secondary-button type="submit" ikon="filter">Saring peran</x-secondary-button>
                </form>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm" data-tabel-data>
                        <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Nama</th><th class="py-2 pe-4 font-medium">Email</th><th class="py-2 pe-4 font-medium">Peran</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($pengguna as $u)
                                <tr class="border-b border-gray-100 last:border-0">
                                    <td class="py-2 pe-4">{{ $u->name }}</td>
                                    <td class="py-2 pe-4">{{ $u->email }}</td>
                                    <td class="py-2 pe-4">{{ $u->roles->map(fn ($r) => \App\Models\Role::ALL[$r->kode] ?? $r->nama)->implode(', ') ?: 'Tanpa peran' }}</td>
                                    <td class="py-2 text-end"><a href="{{ route('sistem.pengguna.edit', $u) }}" class="text-indigo-700 hover:underline">Ubah</a></td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-gray-600">Tidak ada pengguna yang cocok.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
