<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <x-input-label for="judul" value="Judul" />
        <x-text-input id="judul" name="judul" class="mt-1 block w-full" :value="old('judul', $proposal?->judul)" required />
    </div>
    <div class="sm:col-span-2">
        <x-input-label for="ringkasan" value="Ringkasan" />
        <textarea id="ringkasan" name="ringkasan" rows="5" required class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('ringkasan', $proposal?->ringkasan) }}</textarea>
    </div>
    <div>
        <x-input-label for="kata_kunci" value="Kata kunci" />
        <x-text-input id="kata_kunci" name="kata_kunci" class="mt-1 block w-full" :value="old('kata_kunci', $proposal?->kata_kunci)" />
    </div>
    <div>
        <x-input-label for="rumpun_ilmu" value="Rumpun ilmu" />
        <x-text-input id="rumpun_ilmu" name="rumpun_ilmu" class="mt-1 block w-full" :value="old('rumpun_ilmu', $proposal?->rumpun_ilmu)" />
    </div>
    <div class="sm:col-span-2">
        <x-input-label for="bidang_fokus_id" value="Bidang fokus (RIP / Renstra PkM)" />
        <x-select-input id="bidang_fokus_id" name="bidang_fokus_id" class="mt-1 block w-full">
            <option value="">-</option>
            @foreach ($bidangFokus as $bidang)
                <option value="{{ $bidang->id }}" @selected(old('bidang_fokus_id', $proposal?->bidang_fokus_id) == $bidang->id)>{{ $bidang->nama }}</option>
            @endforeach
        </x-select-input>
    </div>
    <div>
        <x-input-label for="tkt_awal" value="TKT awal" />
        <x-text-input id="tkt_awal" name="tkt_awal" type="number" min="1" max="9" class="mt-1 block w-full" :value="old('tkt_awal', $proposal?->tkt_awal)" />
    </div>
    <div>
        <x-input-label for="tkt_target" value="TKT target" />
        <x-text-input id="tkt_target" name="tkt_target" type="number" min="1" max="9" class="mt-1 block w-full" :value="old('tkt_target', $proposal?->tkt_target)" />
    </div>
    @if ($periode->skema->is_multitahun)
        <div>
            <x-input-label for="lama_tahun" value="Lama kegiatan (tahun)" />
            <x-text-input id="lama_tahun" name="lama_tahun" type="number" min="1" :max="$periode->skema->lama_maks_tahun" class="mt-1 block w-full" :value="old('lama_tahun', $proposal?->lama_tahun ?? 1)" />
        </div>
    @endif
</div>
