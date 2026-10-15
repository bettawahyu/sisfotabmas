<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $impor->judul() }}</h2>
    </x-slot>

    <div class="py-6">
        <div class="space-y-6 px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu judul="Unggah berkas CSV" ikon="upload" class="max-w-3xl">
                <p class="mb-4 text-sm text-gray-600">
                    Kolom yang dibutuhkan: <code>{{ implode(', ', $impor->kolom()) }}</code>. Pemisah koma atau titik koma.
                    Baris dengan kode yang sudah ada akan diperbarui; kode baru akan ditambahkan.
                </p>
                <form method="POST" action="{{ route('sistem.impor.periksa', $jenis) }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-3">
                    @csrf
                    <input type="file" name="berkas" accept=".csv,text/csv" required class="text-sm">
                    <x-primary-button ikon="eye">Periksa</x-primary-button>
                    <a href="{{ route('sistem.impor.templat', $jenis) }}" class="inline-flex items-center gap-1 text-sm text-indigo-700 hover:underline"><x-ikon nama="download" /> Unduh templat</a>
                    <a href="{{ route($impor->ruteKembali()) }}" class="text-sm text-gray-600 hover:underline">Kembali</a>
                </form>
            </x-kartu>

            @if ($hasil)
                @php($bergalat = collect($hasil)->filter(fn ($b) => $b['galat'])->count())
                <x-kartu judul="Pratinjau" ikon="eye">
                    <p class="mb-4 text-sm {{ $bergalat ? 'text-red-700' : 'text-gray-600' }}">
                        {{ count($hasil) }} baris dibaca: {{ collect($hasil)->where('aksi', 'baru')->count() }} baru, {{ collect($hasil)->where('aksi', 'perbarui')->count() }} diperbarui.
                        @if ($bergalat)
                            {{ $bergalat }} baris bergalat; perbaiki berkas lalu unggah lagi.
                        @endif
                    </p>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead><tr class="text-left text-gray-500 border-b">
                                <th class="py-2 pe-4 font-medium">#</th>
                                @foreach ($impor->kolom() as $kolom)
                                    <th class="py-2 pe-4 font-medium">{{ $kolom }}</th>
                                @endforeach
                                <th class="py-2 pe-4 font-medium">Hasil</th>
                            </tr></thead>
                            <tbody>
                                @foreach ($hasil as $i => $b)
                                    <tr class="border-b border-gray-100 last:border-0 {{ $b['galat'] ? 'bg-red-50' : '' }}">
                                        <td class="py-2 pe-4 text-gray-500">{{ $i + 2 }}</td>
                                        @foreach ($impor->kolom() as $kolom)
                                            <td class="py-2 pe-4">{{ $b['data'][$kolom] ?? '' }}</td>
                                        @endforeach
                                        <td class="py-2 pe-4">
                                            @if ($b['galat'])
                                                <span class="text-red-700">{{ implode(' ', $b['galat']) }}</span>
                                            @else
                                                {{ $b['aksi'] === 'baru' ? 'Baru' : 'Diperbarui' }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @unless ($bergalat)
                        <form method="POST" action="{{ route('sistem.impor.simpan', $jenis) }}" class="mt-4">
                            @csrf
                            <x-primary-button ikon="save">Simpan {{ count($hasil) }} baris</x-primary-button>
                        </form>
                    @endunless
                </x-kartu>
            @endif
        </div>
    </div>
</x-app-layout>
