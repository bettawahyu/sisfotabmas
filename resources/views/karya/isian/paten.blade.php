@php use App\Models\Paten; @endphp
<div class="sm:col-span-2">
    <x-input-label for="judul" value="Judul invensi" />
    <x-text-input id="judul" name="judul" class="mt-1 block w-full" :value="old('judul', $karya->judul)" required />
</div>
<div>
    <x-input-label for="jenis" value="Jenis" />
    <x-select-input id="jenis" name="jenis" class="mt-1 block w-full">
        @foreach (Paten::JENIS as $kode => $nama)
            <option value="{{ $kode }}" @selected(old('jenis', $karya->jenis) === $kode)>{{ $nama }}</option>
        @endforeach
    </x-select-input>
</div>
<div>
    <x-input-label for="status" value="Status" />
    <x-select-input id="status" name="status" class="mt-1 block w-full">
        @foreach (Paten::STATUS as $kode => $nama)
            <option value="{{ $kode }}" @selected(old('status', $karya->status) === $kode)>{{ $nama }}</option>
        @endforeach
    </x-select-input>
</div>
<div>
    <x-input-label for="pemegang_paten" value="Pemegang paten" />
    <x-text-input id="pemegang_paten" name="pemegang_paten" class="mt-1 block w-full" :value="old('pemegang_paten', $karya->pemegang_paten)" />
</div>
<div>
    <x-input-label for="konsultan_ki" value="Konsultan KI" />
    <x-text-input id="konsultan_ki" name="konsultan_ki" class="mt-1 block w-full" :value="old('konsultan_ki', $karya->konsultan_ki)" />
</div>
<div>
    <x-input-label for="no_permohonan" value="Nomor permohonan" />
    <x-text-input id="no_permohonan" name="no_permohonan" class="mt-1 block w-full" :value="old('no_permohonan', $karya->no_permohonan)" />
</div>
<div>
    <x-input-label for="tgl_penerimaan" value="Tanggal penerimaan" />
    <x-text-input id="tgl_penerimaan" name="tgl_penerimaan" type="date" class="mt-1 block w-full" :value="old('tgl_penerimaan', $karya->tgl_penerimaan?->format('Y-m-d'))" />
</div>
<div>
    <x-input-label for="tgl_publikasi" value="Tanggal diumumkan" />
    <x-text-input id="tgl_publikasi" name="tgl_publikasi" type="date" class="mt-1 block w-full" :value="old('tgl_publikasi', $karya->tgl_publikasi?->format('Y-m-d'))" />
</div>
<div>
    <x-input-label for="klasifikasi_ipc" value="Klasifikasi IPC" />
    <x-text-input id="klasifikasi_ipc" name="klasifikasi_ipc" class="mt-1 block w-full" :value="old('klasifikasi_ipc', $karya->klasifikasi_ipc)" />
</div>
<div>
    <x-input-label for="jumlah_klaim" value="Jumlah klaim" />
    <x-text-input id="jumlah_klaim" name="jumlah_klaim" type="number" class="mt-1 block w-full" :value="old('jumlah_klaim', $karya->jumlah_klaim)" min="1" />
</div>
<div>
    <x-input-label for="no_paten" value="Nomor paten" />
    <x-text-input id="no_paten" name="no_paten" class="mt-1 block w-full" :value="old('no_paten', $karya->no_paten)" />
</div>
<div>
    <x-input-label for="tgl_pemberian" value="Tanggal pemberian" />
    <x-text-input id="tgl_pemberian" name="tgl_pemberian" type="date" class="mt-1 block w-full" :value="old('tgl_pemberian', $karya->tgl_pemberian?->format('Y-m-d'))" />
</div>
<div>
    <x-input-label for="masa_berlaku_sampai" value="Berlaku sampai" />
    <x-text-input id="masa_berlaku_sampai" name="masa_berlaku_sampai" type="date" class="mt-1 block w-full" :value="old('masa_berlaku_sampai', $karya->masa_berlaku_sampai?->format('Y-m-d'))" />
</div>
<div>
    <x-input-label for="url_pdki" value="Tautan PDKI" />
    <x-text-input id="url_pdki" name="url_pdki" type="url" class="mt-1 block w-full" :value="old('url_pdki', $karya->url_pdki)" />
</div>
<div class="sm:col-span-2">
    <x-input-label for="abstrak" value="Abstrak" />
    <textarea id="abstrak" name="abstrak" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('abstrak', $karya->abstrak) }}</textarea>
</div>
