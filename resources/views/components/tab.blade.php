{{--
    Tabs in the Dash UI style. Each item has a label and either an href (a tab that
    is its own page) or a key (a tab switched in place through Alpine's `tab`).
--}}
@props(['daftar'])
@php
    $dasar = 'inline-flex items-center gap-2 whitespace-nowrap border-b-2 -mb-px px-4 py-3 text-sm font-medium transition';
    $aktif = 'border-indigo-600 text-indigo-600';
    $pasif = 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700';
@endphp
<nav {{ $attributes->merge(['class' => 'flex overflow-x-auto border-b border-gray-200']) }} role="tablist">
    @foreach ($daftar as $item)
        @isset($item['href'])
            <a href="{{ $item['href'] }}" role="tab" aria-selected="{{ ! empty($item['aktif']) ? 'true' : 'false' }}" class="{{ $dasar }} {{ ! empty($item['aktif']) ? $aktif : $pasif }}">
        @else
            <button type="button" role="tab" x-on:click="tab = @js($item['kunci'])" x-bind:aria-selected="tab === @js($item['kunci'])"
                x-bind:class="tab === @js($item['kunci']) ? @js($aktif) : @js($pasif)" class="{{ $dasar }}">
        @endisset
            @isset($item['ikon'])<x-ikon :nama="$item['ikon']" />@endisset
            {{ $item['label'] }}
            @isset($item['jumlah'])
                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">{{ $item['jumlah'] }}</span>
            @endisset
        @isset($item['href'])
            </a>
        @else
            </button>
        @endisset
    @endforeach
</nav>
