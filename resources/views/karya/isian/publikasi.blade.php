@php
    use App\Models\PeringkatJurnal;
    use App\Models\Publikasi;

    // Inactive ranks are no longer offered, except the one this entry already has.
    $daftarPeringkat = PeringkatJurnal::berurutan()->where(fn ($q) => $q->where('is_active', true)->orWhere('kode', $karya->peringkat))->get();
@endphp
<div class="sm:col-span-2">
    <x-input-label for="judul" value="Judul" ikon="type" />
    <x-text-input id="judul" name="judul" class="mt-1 block w-full" :value="old('judul', $karya->judul)" required />
</div>
<div>
    <x-input-label for="jenis" value="Jenis" ikon="tag" />
    <x-select-input id="jenis" name="jenis" class="mt-1 block w-full">
        @foreach (Publikasi::JENIS as $kode => $nama)
            <option value="{{ $kode }}" @selected(old('jenis', $karya->jenis) === $kode)>{{ $nama }}</option>
        @endforeach
    </x-select-input>
</div>
<div>
    <x-input-label for="status" value="Status" ikon="flag" />
    <x-select-input id="status" name="status" class="mt-1 block w-full">
        @foreach (Publikasi::STATUS as $kode => $nama)
            <option value="{{ $kode }}" @selected(old('status', $karya->status) === $kode)>{{ $nama }}</option>
        @endforeach
    </x-select-input>
</div>
<div class="sm:col-span-2">
    <x-input-label for="nama_wadah" value="Nama jurnal, prosiding, atau penerbit" ikon="book" />
    <x-text-input id="nama_wadah" name="nama_wadah" class="mt-1 block w-full" :value="old('nama_wadah', $karya->nama_wadah)" />
</div>
<div>
    <x-input-label for="peringkat" value="Peringkat" ikon="star" />
    <x-select-input id="peringkat" name="peringkat" class="mt-1 block w-full">
        <option value="">-</option>
        @foreach ($daftarPeringkat as $p)
            <option value="{{ $p->kode }}" @selected(old('peringkat', $karya->peringkat) === $p->kode)>{{ $p->nama }}</option>
        @endforeach
    </x-select-input>
</div>
<div>
    <x-input-label for="issn_isbn" value="ISSN atau ISBN" ikon="hash" />
    <x-text-input id="issn_isbn" name="issn_isbn" class="mt-1 block w-full" :value="old('issn_isbn', $karya->issn_isbn)" />
</div>
<div>
    <x-input-label for="volume" value="Volume" ikon="book-open" />
    <x-text-input id="volume" name="volume" class="mt-1 block w-full" :value="old('volume', $karya->volume)" />
</div>
<div>
    <x-input-label for="nomor" value="Nomor" ikon="hash" />
    <x-text-input id="nomor" name="nomor" class="mt-1 block w-full" :value="old('nomor', $karya->nomor)" />
</div>
<div>
    <x-input-label for="halaman" value="Halaman" ikon="book-open" />
    <x-text-input id="halaman" name="halaman" class="mt-1 block w-full" :value="old('halaman', $karya->halaman)" />
</div>
<div>
    <x-input-label for="tahun" value="Tahun" ikon="calendar" />
    <x-text-input id="tahun" name="tahun" type="number" class="mt-1 block w-full" :value="old('tahun', $karya->tahun)" required min="1950" />
</div>
<div>
    <x-input-label for="tgl_terbit" value="Tanggal terbit" ikon="calendar" />
    <x-text-input id="tgl_terbit" name="tgl_terbit" type="date" class="mt-1 block w-full" :value="old('tgl_terbit', $karya->tgl_terbit?->format('Y-m-d'))" />
</div>
<div>
    <x-input-label for="doi" value="DOI" ikon="link" />
    <x-text-input id="doi" name="doi" class="mt-1 block w-full" :value="old('doi', $karya->doi)" placeholder="10.xxxx/xxxxx" />
</div>
<div class="sm:col-span-2">
    <x-input-label for="url" value="Tautan artikel" ikon="link" />
    <x-text-input id="url" name="url" type="url" class="mt-1 block w-full" :value="old('url', $karya->url)" />
</div>
<div class="sm:col-span-2">
    <x-input-label for="abstrak" value="Abstrak" ikon="align-left" />
    <textarea id="abstrak" name="abstrak" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('abstrak', $karya->abstrak) }}</textarea>
</div>
