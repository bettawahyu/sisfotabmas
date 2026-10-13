@php
    use App\Models\TemplateDokumen;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $template->exists ? 'Ubah Templat' : 'Templat Baru' }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <form method="POST" enctype="multipart/form-data" action="{{ $template->exists ? route('admin.template.update', $template) : route('admin.template.store') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    @if ($template->exists)
                        @method('PUT')
                    @endif
                    <div class="sm:col-span-2">
                        <x-input-label for="nama" value="Nama" />
                        <x-text-input id="nama" name="nama" class="mt-1 block w-full" :value="old('nama', $template->nama)" required />
                    </div>
                    <div>
                        <x-input-label for="jenis" value="Jenis dokumen" />
                        <x-select-input id="jenis" name="jenis" class="mt-1 block w-full">
                            @foreach (TemplateDokumen::JENIS as $kode => $label)
                                <option value="{{ $kode }}" @selected(old('jenis', $template->jenis) === $kode)>{{ $label }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="skema_id" value="Skema" />
                        <x-select-input id="skema_id" name="skema_id" class="mt-1 block w-full">
                            <option value="">Semua skema</option>
                            @foreach ($skema as $s)
                                <option value="{{ $s->id }}" @selected((string) old('skema_id', $template->skema_id) === (string) $s->id)>{{ $s->nama }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="tahun" value="Tahun (kosongkan untuk semua tahun)" />
                        <x-text-input id="tahun" name="tahun" type="number" min="2000" max="2100" class="mt-1 block w-full" :value="old('tahun', $template->tahun)" />
                    </div>
                    <div>
                        <x-input-label for="versi" value="Versi (opsional)" />
                        <x-text-input id="versi" name="versi" class="mt-1 block w-full" :value="old('versi', $template->versi)" />
                    </div>
                    <div class="sm:col-span-2">
                        <x-input-label for="berkas" :value="$template->exists ? 'Ganti berkas (opsional)' : 'Berkas'" />
                        @if ($template->berkasTerbaru)
                            <p class="mt-1 text-sm text-gray-700">Saat ini: <a class="text-indigo-700 underline" href="{{ route('templat.unduh', $template) }}">{{ $template->berkasTerbaru->nama_asli }}</a></p>
                        @endif
                        <input id="berkas" type="file" name="berkas" accept=".pdf,.doc,.docx,.xls,.xlsx" @required(! $template->exists) class="mt-2 text-sm">
                        <p class="mt-1 text-xs text-gray-500">PDF, Word, atau Excel, maksimal 10 MB.</p>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $template->is_active)) class="rounded border-gray-300">
                        Aktif
                    </label>
                    <div class="sm:col-span-2"><x-primary-button>Simpan</x-primary-button></div>
                </form>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
