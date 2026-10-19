<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Kualifikasi Dosen</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <p class="text-sm text-gray-600 mb-6">NIDN atau NUPTK dan ID SINTA wajib diisi sebelum mengirim proposal.</p>
                <form method="POST" action="{{ route('profil-dosen.update') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    @method('PUT')
                    <div class="sm:col-span-2">
                        <x-input-label for="nama" value="Nama (tanpa gelar)" ikon="user" />
                        <x-text-input id="nama" name="nama" class="mt-1 block w-full" :value="old('nama', $dosen?->nama ?? auth()->user()->name)" required />
                    </div>
                    <div>
                        <x-input-label for="gelar_depan" value="Gelar depan" ikon="award" />
                        <x-text-input id="gelar_depan" name="gelar_depan" class="mt-1 block w-full" :value="old('gelar_depan', $dosen?->gelar_depan)" />
                    </div>
                    <div>
                        <x-input-label for="gelar_belakang" value="Gelar belakang" ikon="award" />
                        <x-text-input id="gelar_belakang" name="gelar_belakang" class="mt-1 block w-full" :value="old('gelar_belakang', $dosen?->gelar_belakang)" />
                    </div>
                    <div>
                        <x-input-label for="nidn" value="NIDN" ikon="hash" />
                        <x-text-input id="nidn" name="nidn" class="mt-1 block w-full" :value="old('nidn', $dosen?->nidn)" />
                    </div>
                    <div>
                        <x-input-label for="nuptk" value="NUPTK" ikon="hash" />
                        <x-text-input id="nuptk" name="nuptk" class="mt-1 block w-full" :value="old('nuptk', $dosen?->nuptk)" />
                    </div>
                    <div>
                        <x-input-label for="sinta_id" value="ID SINTA" ikon="hash" />
                        <x-text-input id="sinta_id" name="sinta_id" class="mt-1 block w-full" :value="old('sinta_id', $dosen?->sinta_id)" />
                    </div>
                    <div>
                        <x-input-label for="jabatan_fungsional" value="Jabatan fungsional" ikon="briefcase" />
                        <x-select-input id="jabatan_fungsional" name="jabatan_fungsional" class="mt-1 block w-full">
                            <option value="">-</option>
                            @foreach (App\Enums\JabatanFungsional::cases() as $jabatan)
                                <option value="{{ $jabatan->value }}" @selected(old('jabatan_fungsional', $dosen?->jabatan_fungsional?->value) === $jabatan->value)>{{ $jabatan->label() }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label value="Pendidikan terakhir" ikon="book-open" />
                        <p class="mt-1 block w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">{{ $dosen?->pendidikan_terakhir ?? 'Belum ada' }}</p>
                        <p class="mt-1 text-xs text-gray-500">Diambil dari jenjang tertinggi di riwayat pendidikan.</p>
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="google_scholar_id" value="Google Scholar (ID atau URL profil, opsional)" ikon="link" />
                        <x-text-input id="google_scholar_id" name="google_scholar_id" class="mt-1 block w-full" :value="old('google_scholar_id', $dosen?->google_scholar_id)" placeholder="https://scholar.google.com/citations?user=..." />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="prodi_id" value="Program studi (homebase)" ikon="book" />
                        <x-select-input id="prodi_id" name="prodi_id" class="mt-1 block w-full">
                            <option value="">-</option>
                            @foreach ($prodi as $p)
                                <option value="{{ $p->id }}" @selected(old('prodi_id', $dosen?->prodi_id) == $p->id)>{{ $p->jenjang }} {{ $p->nama }} ({{ $p->fakultas->nama }})</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div class="sm:col-span-2">
                        <x-primary-button ikon="save">Simpan</x-primary-button>
                    </div>
                </form>
            </x-kartu>

            <x-kartu judul="Riwayat Pendidikan" ikon="book-open" class="mt-6">
                @if ($dosen)
                    <p class="mb-4 text-sm text-gray-600">Jenjang tertinggi menjadi pendidikan terakhir yang dicek terhadap syarat pendidikan minimal skema hibah.</p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Jenjang</th><th class="py-2 pe-4 font-medium">Perguruan tinggi</th><th class="py-2 pe-4 font-medium">Program studi</th><th class="py-2 pe-4 font-medium">Lulus</th><th></th></tr></thead>
                            <tbody>
                                @forelse ($dosen->riwayatPendidikan as $r)
                                    <tr class="border-b border-gray-100 last:border-0 align-top">
                                        <td class="py-2 pe-4 font-medium">{{ $r->jenjang }}</td>
                                        <td class="py-2 pe-4">{{ $r->institusi }}@if ($r->negara && $r->negara !== 'Indonesia'), {{ $r->negara }}@endif</td>
                                        <td class="py-2 pe-4">{{ $r->program_studi ?? '-' }}</td>
                                        <td class="py-2 pe-4">{{ $r->tahun_lulus ?? '-' }}</td>
                                        <td class="py-2 text-end whitespace-nowrap">
                                            <a href="{{ route('profil-dosen.pendidikan.edit', $r) }}" class="text-indigo-700 hover:underline">Ubah</a>
                                            <form method="POST" action="{{ route('profil-dosen.pendidikan.destroy', $r) }}" class="inline" data-konfirmasi="Hapus riwayat pendidikan ini?">
                                                @csrf
                                                @method('DELETE')
                                                <button class="ms-3 text-red-700 hover:underline">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-4 text-gray-600">Belum ada riwayat pendidikan.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h4 class="mt-6 mb-3 text-sm font-semibold text-gray-800">Tambah pendidikan</h4>
                    <form method="POST" action="{{ route('profil-dosen.pendidikan.store') }}" class="grid gap-4 sm:grid-cols-2">
                        @csrf
                        @include('dosen._isian-pendidikan', ['riwayat' => null])
                        <div class="sm:col-span-2"><x-primary-button ikon="plus">Tambah</x-primary-button></div>
                    </form>
                @else
                    <p class="text-sm text-gray-600">Simpan kualifikasi dosen di atas terlebih dahulu, lalu tambahkan riwayat pendidikan.</p>
                @endif
            </x-kartu>
        </div>
    </div>
</x-app-layout>
