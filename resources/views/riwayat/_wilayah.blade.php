{{--
    Province then regency or city. Only the regency is stored; the province
    follows from it. $nilai is the regency code already chosen.
--}}
@php($kodeAwal = old('kabupaten_kota_kode', $nilai))
<div x-data="{ wilayah: @js($wilayah), provinsi: @js($kodeAwal ? substr($kodeAwal, 0, 2) : ''), kabupaten: @js($kodeAwal ?? '') }" class="contents">
    <div>
        <x-input-label for="{{ $awalan ?? '' }}provinsi" value="Provinsi" ikon="map" />
        <x-select-input id="{{ $awalan ?? '' }}provinsi" class="mt-1 block w-full" x-model="provinsi" x-on:change="kabupaten = ''">
            <option value="">Pilih provinsi</option>
            <template x-for="p in wilayah" :key="p.kode">
                <option :value="p.kode" x-text="p.nama" :selected="p.kode === provinsi"></option>
            </template>
        </x-select-input>
    </div>
    <div>
        <x-input-label for="{{ $awalan ?? '' }}kabupaten_kota_kode" value="Kabupaten/kota" ikon="map-pin" />
        <x-select-input id="{{ $awalan ?? '' }}kabupaten_kota_kode" name="kabupaten_kota_kode" class="mt-1 block w-full" x-model="kabupaten" x-bind:disabled="! provinsi">
            <option value="">Pilih kabupaten/kota</option>
            <template x-for="k in (wilayah.find(p => p.kode === provinsi)?.kabupaten ?? [])" :key="k.kode">
                <option :value="k.kode" x-text="k.nama" :selected="k.kode === kabupaten"></option>
            </template>
        </x-select-input>
    </div>
</div>
