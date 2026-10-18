@php
    // Image fields: slug => [input name, label, accepted types, hint, preview background].
    $gambar = [
        'logo' => ['logo', 'Logo', '.png,.jpg,.jpeg,.webp', 'PNG, JPG atau WEBP, maks. 1 MB. Disarankan PNG transparan. Dipakai di landing, login dan sidebar.', 'bg-gray-100'],
        'logo-gelap' => ['logo_gelap', 'Logo untuk latar gelap (opsional)', '.png,.jpg,.jpeg,.webp', 'Isi bila logo utama kurang terbaca di sidebar dan landing yang gelap. Bila kosong, logo utama yang dipakai.', 'bg-gray-800'],
        'favicon' => ['favicon', 'Favicon', '.png,.ico', 'PNG atau ICO, maks. 512 KB, disarankan persegi 64×64 px.', 'bg-gray-100'],
        'latar' => ['latar', 'Gambar latar landing', '.png,.jpg,.jpeg,.webp', 'JPG, PNG atau WEBP, maks. 3 MB, disarankan 1920×1080 px. Diberi lapisan gelap agar teks tetap terbaca.', 'bg-gray-100'],
    ];
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Identitas Aplikasi</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-pesan />

            <form method="POST" action="{{ route('sistem.identitas.update') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                <x-kartu judul="Nama" ikon="type">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <x-input-label for="nama" value="Nama aplikasi" ikon="edit-3" />
                            <x-text-input id="nama" name="nama" class="mt-1 block w-full" maxlength="100" :value="old('nama', $identitas->nama())" required />
                            <p class="mt-1 text-xs text-gray-500">Tampil di judul tab, landing, login, footer dan email.</p>
                        </div>
                        <div>
                            <x-input-label for="nama_singkat" value="Nama singkat" ikon="hash" />
                            <x-text-input id="nama_singkat" name="nama_singkat" class="mt-1 block w-full" maxlength="20"
                                          :value="old('nama_singkat', $identitas->tersimpan('identitas.nama_singkat'))" />
                            <p class="mt-1 text-xs text-gray-500">Tampil di sidebar. Bila kosong, nama aplikasi yang dipakai.</p>
                        </div>
                    </div>
                </x-kartu>

                <x-kartu judul="Logo dan gambar" ikon="image">
                    <div class="grid gap-6 sm:grid-cols-2">
                        @foreach ($gambar as $jenis => [$input, $label, $terima, $petunjuk, $latarPratinjau])
                            <div>
                                <x-input-label :for="$input" :value="$label" />
                                <div class="mt-2 flex items-center gap-4">
                                    <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-md border border-gray-300 {{ $latarPratinjau }}">
                                        @if ($identitas->berkas($jenis))
                                            <img src="{{ $identitas->url($jenis) }}" alt="{{ $label }} saat ini" class="max-h-full max-w-full object-contain">
                                        @else
                                            <span class="px-1 text-center text-xs text-gray-500">Bawaan</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0 space-y-2">
                                        <input id="{{ $input }}" type="file" name="{{ $input }}" accept="{{ $terima }}" class="block w-full text-sm">
                                        @if ($identitas->berkas($jenis))
                                            <button type="submit" form="hapus-{{ $jenis }}" class="text-sm text-red-600 hover:underline">Kembalikan ke bawaan</button>
                                        @endif
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">{{ $petunjuk }}</p>
                                <x-input-error :messages="$errors->get($input)" class="mt-1" />
                            </div>
                        @endforeach
                    </div>
                </x-kartu>

                <x-kartu judul="Landing" ikon="monitor">
                    <x-input-label for="deskripsi" value="Deskripsi landing" ikon="align-left" />
                    <textarea id="deskripsi" name="deskripsi" rows="4" maxlength="1000" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('deskripsi', $identitas->tersimpan('landing.deskripsi')) }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">Teks biasa, maks. 1.000 karakter; baris baru dipertahankan. Bila kosong, deskripsi bawaan yang dipakai.</p>
                </x-kartu>

                <div class="flex items-center gap-3">
                    <x-primary-button ikon="save">Simpan</x-primary-button>
                    <a href="{{ url('/') }}" target="_blank" class="text-sm text-gray-600 hover:underline">Lihat landing</a>
                </div>
            </form>

            {{-- Forms for the "back to default" buttons, kept outside the main form. --}}
            @foreach (array_keys($gambar) as $jenis)
                @if ($identitas->berkas($jenis))
                    <form id="hapus-{{ $jenis }}" method="POST" action="{{ route('sistem.identitas.berkas.destroy', $jenis) }}" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                @endif
            @endforeach
        </div>
    </div>
</x-app-layout>
