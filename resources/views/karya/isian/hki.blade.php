@php use App\Models\Hki; @endphp
<div class="sm:col-span-2">
    <x-input-label for="judul" value="Judul" ikon="type" />
    <x-text-input id="judul" name="judul" class="mt-1 block w-full" :value="old('judul', $karya->judul)" required />
</div>
<div>
    <x-input-label for="jenis" value="Jenis" ikon="tag" />
    <x-select-input id="jenis" name="jenis" class="mt-1 block w-full">
        @foreach (Hki::JENIS as $kode => $nama)
            <option value="{{ $kode }}" @selected(old('jenis', $karya->jenis) === $kode)>{{ $nama }}</option>
        @endforeach
    </x-select-input>
</div>
<div>
    <x-input-label for="jenis_ciptaan" value="Jenis ciptaan" ikon="tag" />
    <x-text-input id="jenis_ciptaan" name="jenis_ciptaan" class="mt-1 block w-full" :value="old('jenis_ciptaan', $karya->jenis_ciptaan)" placeholder="Program komputer, buku, karya tulis..." />
</div>
<div>
    <x-input-label for="status" value="Status" ikon="flag" />
    <x-select-input id="status" name="status" class="mt-1 block w-full">
        @foreach (Hki::STATUS as $kode => $nama)
            <option value="{{ $kode }}" @selected(old('status', $karya->status) === $kode)>{{ $nama }}</option>
        @endforeach
    </x-select-input>
</div>
<div>
    <x-input-label for="pemegang_hak" value="Pemegang hak" ikon="users" />
    <x-text-input id="pemegang_hak" name="pemegang_hak" class="mt-1 block w-full" :value="old('pemegang_hak', $karya->pemegang_hak)" />
</div>
<div>
    <x-input-label for="no_permohonan" value="Nomor permohonan" ikon="hash" />
    <x-text-input id="no_permohonan" name="no_permohonan" class="mt-1 block w-full" :value="old('no_permohonan', $karya->no_permohonan)" />
</div>
<div>
    <x-input-label for="tgl_permohonan" value="Tanggal permohonan" ikon="calendar" />
    <x-text-input id="tgl_permohonan" name="tgl_permohonan" type="date" class="mt-1 block w-full" :value="old('tgl_permohonan', $karya->tgl_permohonan?->format('Y-m-d'))" />
</div>
<div>
    <x-input-label for="no_pencatatan" value="Nomor pencatatan atau pendaftaran" ikon="hash" />
    <x-text-input id="no_pencatatan" name="no_pencatatan" class="mt-1 block w-full" :value="old('no_pencatatan', $karya->no_pencatatan)" />
</div>
<div>
    <x-input-label for="tgl_pencatatan" value="Tanggal pencatatan" ikon="calendar" />
    <x-text-input id="tgl_pencatatan" name="tgl_pencatatan" type="date" class="mt-1 block w-full" :value="old('tgl_pencatatan', $karya->tgl_pencatatan?->format('Y-m-d'))" />
</div>
<div>
    <x-input-label for="masa_berlaku_sampai" value="Berlaku sampai" ikon="calendar" />
    <x-text-input id="masa_berlaku_sampai" name="masa_berlaku_sampai" type="date" class="mt-1 block w-full" :value="old('masa_berlaku_sampai', $karya->masa_berlaku_sampai?->format('Y-m-d'))" />
</div>
<div>
    <x-input-label for="url_pdki" value="Tautan PDKI" ikon="link" />
    <x-text-input id="url_pdki" name="url_pdki" type="url" class="mt-1 block w-full" :value="old('url_pdki', $karya->url_pdki)" />
</div>
<div class="sm:col-span-2">
    <x-input-label for="uraian" value="Uraian" ikon="align-left" />
    <textarea id="uraian" name="uraian" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('uraian', $karya->uraian) }}</textarea>
</div>
