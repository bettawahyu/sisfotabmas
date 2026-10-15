@php use App\Services\AlurKarya; @endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Unduh Data</h2>
    </x-slot>

    <div class="py-6">
        <div class="grid gap-6 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
            <x-kartu judul="Proposal" ikon="file-text">
                <p class="mb-4 text-sm text-gray-600">Semua proposal yang sudah diajukan (draf tidak ikut), satu baris per proposal. Kosongkan filter untuk mengunduh semuanya.</p>
                <form method="GET" action="{{ route('unduh-data.proposal') }}" class="grid gap-4">
                    <div>
                        <x-input-label for="proposal_tahun" value="Tahun anggaran" ikon="calendar" />
                        <x-select-input id="proposal_tahun" name="tahun" class="mt-1 block w-full">
                            <option value="">Semua tahun</option>
                            @foreach ($tahun as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="proposal_skema" value="Skema" ikon="layers" />
                        <x-select-input id="proposal_skema" name="skema_id" class="mt-1 block w-full">
                            <option value="">Semua skema</option>
                            @foreach ($skema as $s)
                                <option value="{{ $s->id }}">{{ $s->nama }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="proposal_prodi" value="Program studi ketua" ikon="book" />
                        <x-select-input id="proposal_prodi" name="prodi_id" class="mt-1 block w-full">
                            <option value="">Semua program studi</option>
                            @foreach ($prodi as $p)
                                <option value="{{ $p->id }}">{{ $p->jenjang }} {{ $p->nama }} ({{ $p->fakultas->nama }})</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div><x-primary-button ikon="download">Unduh Excel</x-primary-button></div>
                </form>
            </x-kartu>

            <x-kartu judul="Publikasi dan KI" ikon="book-open">
                <p class="mb-4 text-sm text-gray-600">Satu file dengan sheet Publikasi, HKI, dan Paten. Tahun HKI dan paten diambil dari tanggal pencatatan atau pemberian, atau tanggal permohonan bila belum ada.</p>
                <form method="GET" action="{{ route('unduh-data.karya') }}" class="grid gap-4">
                    <div>
                        <x-input-label for="karya_tahun" value="Tahun" ikon="calendar" />
                        <x-select-input id="karya_tahun" name="tahun" class="mt-1 block w-full">
                            <option value="">Semua tahun</option>
                            @foreach ($tahun as $t)
                                <option value="{{ $t }}">{{ $t }}</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="karya_jenis" value="Jenis luaran" ikon="package" />
                        <x-select-input id="karya_jenis" name="jenis" class="mt-1 block w-full">
                            <option value="">Semua jenis</option>
                            @foreach ($jenisKarya as $kelompok => $daftar)
                                <optgroup label="{{ AlurKarya::LABEL[$kelompok] }}">
                                    <option value="{{ $kelompok }}">Semua {{ AlurKarya::LABEL[$kelompok] }}</option>
                                    @foreach ($daftar as $kode => $label)
                                        <option value="{{ $kelompok }}:{{ $kode }}">{{ $label }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </x-select-input>
                    </div>
                    <div>
                        <x-input-label for="karya_prodi" value="Program studi dosen" ikon="book" />
                        <x-select-input id="karya_prodi" name="prodi_id" class="mt-1 block w-full">
                            <option value="">Semua program studi</option>
                            @foreach ($prodi as $p)
                                <option value="{{ $p->id }}">{{ $p->jenjang }} {{ $p->nama }} ({{ $p->fakultas->nama }})</option>
                            @endforeach
                        </x-select-input>
                    </div>
                    <label class="flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="hanya_valid" value="1" checked class="rounded border-gray-300">
                        Hanya data yang sudah divalidasi LPPM
                    </label>
                    <div><x-primary-button ikon="download">Unduh Excel</x-primary-button></div>
                </form>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
