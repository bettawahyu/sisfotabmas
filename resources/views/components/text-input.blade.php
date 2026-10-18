@props(['disabled' => false])

@php
    $dasar = 'border-gray-300 focus:border-indigo-600 focus:ring-indigo-600 rounded-md shadow-sm text-gray-800';
@endphp

@if ($attributes->get('type') === 'password')
    @php
        // Margins and width go on the wrapper so the show/hide button sits inside the field's right edge.
        $kelas = collect(preg_split('/\s+/', trim((string) $attributes->get('class', ''))))->filter();
        $luar = $kelas->filter(fn ($k) => preg_match('/^(-?m[tbyxse]?-|(sm:|md:|lg:)?w-)/', $k));
    @endphp
    <div class="relative {{ $luar->implode(' ') }}" x-data="{ lihat: false }">
        <input @disabled($disabled) :type="lihat ? 'text' : 'password'"
               {{ $attributes->except('class')->merge(['class' => $dasar.' w-full pe-10 '.$kelas->diff($luar)->implode(' ')]) }}>
        <button type="button" @click="lihat = ! lihat" class="absolute inset-y-0 end-0 flex items-center px-3 text-gray-500 hover:text-gray-800"
                :aria-pressed="lihat.toString()" :aria-label="lihat ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'"
                aria-label="Tampilkan kata sandi" title="Tampilkan atau sembunyikan kata sandi">
            <span x-show="! lihat"><x-ikon nama="eye" /></span>
            <span x-show="lihat" style="display: none;"><x-ikon nama="eye-off" /></span>
        </button>
    </div>
@else
    <input @disabled($disabled) {{ $attributes->merge(['class' => $dasar]) }}>
@endif
