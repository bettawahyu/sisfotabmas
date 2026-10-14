@props(['ikon' => null])
<a {{ $attributes->merge(['class' => 'flex w-full items-center gap-2 rounded-md px-3 py-2 text-start text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out']) }}>
    @if ($ikon)
        <x-ikon :nama="$ikon" />
    @endif
    {{ $slot }}
</a>
