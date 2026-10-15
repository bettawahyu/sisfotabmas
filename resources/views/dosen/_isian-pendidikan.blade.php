@php use App\Models\Dosen; @endphp
<div>
    <x-input-label for="jenjang" value="Jenjang" ikon="award" />
    <x-select-input id="jenjang" name="jenjang" class="mt-1 block w-full" required>
        @foreach (Dosen::PENDIDIKAN as $jenjang)
            <option value="{{ $jenjang }}" @selected(old('jenjang', $riwayat?->jenjang) === $jenjang)>{{ $jenjang }}</option>
        @endforeach
    </x-select-input>
    <x-input-error :messages="$errors->get('jenjang')" class="mt-2" />
</div>
<div>
    <x-input-label for="tahun_lulus" value="Tahun lulus" ikon="calendar" />
    <x-text-input id="tahun_lulus" name="tahun_lulus" type="number" class="mt-1 block w-full" :value="old('tahun_lulus', $riwayat?->tahun_lulus)" />
    <x-input-error :messages="$errors->get('tahun_lulus')" class="mt-2" />
</div>
<div class="sm:col-span-2">
    <x-input-label for="institusi" value="Perguruan tinggi" ikon="home" />
    <x-text-input id="institusi" name="institusi" class="mt-1 block w-full" :value="old('institusi', $riwayat?->institusi)" required />
    <x-input-error :messages="$errors->get('institusi')" class="mt-2" />
</div>
<div>
    <x-input-label for="program_studi_pendidikan" value="Program studi" ikon="book" />
    <x-text-input id="program_studi_pendidikan" name="program_studi" class="mt-1 block w-full" :value="old('program_studi', $riwayat?->program_studi)" />
    <x-input-error :messages="$errors->get('program_studi')" class="mt-2" />
</div>
<div>
    <x-input-label for="negara" value="Negara" ikon="globe" />
    <x-text-input id="negara" name="negara" class="mt-1 block w-full" :value="old('negara', $riwayat?->negara ?? 'Indonesia')" />
    <x-input-error :messages="$errors->get('negara')" class="mt-2" />
</div>
<div class="sm:col-span-2">
    <x-input-label for="judul_tugas_akhir" value="Judul skripsi, tesis, atau disertasi (opsional)" ikon="file-text" />
    <x-text-input id="judul_tugas_akhir" name="judul_tugas_akhir" class="mt-1 block w-full" :value="old('judul_tugas_akhir', $riwayat?->judul_tugas_akhir)" />
    <x-input-error :messages="$errors->get('judul_tugas_akhir')" class="mt-2" />
</div>
