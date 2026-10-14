<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Pengguna dan Peran</h2>
            <a href="{{ route('sistem.pengguna.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">Tambah pengguna</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <form method="GET" action="{{ route('sistem.pengguna.index') }}" class="mb-4 flex flex-wrap gap-3">
                    <x-text-input name="cari" :value="$cari" placeholder="Cari nama atau email" class="flex-1" />
                    <x-select-input name="peran">
                        <option value="">Semua peran</option>
                        @foreach (\App\Models\Role::ALL as $kode => $nama)
                            <option value="{{ $kode }}" @selected($peran === $kode)>{{ $nama }}</option>
                        @endforeach
                    </x-select-input>
                    <x-secondary-button type="submit">Cari</x-secondary-button>
                </form>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
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
                <div class="mt-4">{{ $pengguna->links() }}</div>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
