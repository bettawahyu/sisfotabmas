@props(['ikon' => null, 'varian' => 'utama'])
@php
    $kelas = match ($varian) {
        'kedua' => 'bg-white border-gray-300 text-gray-700 shadow-sm hover:bg-gray-100 hover:text-gray-800',
        default => 'bg-indigo-600 border-transparent text-white hover:bg-indigo-700',
    };
@endphp
<a {{ $attributes->merge(['class' => "inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 $kelas"]) }}>
    @if ($ikon)
        <x-ikon :nama="$ikon" />
    @endif
    {{ $slot }}
</a>
