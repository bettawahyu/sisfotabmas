@props(['ikon' => null])
<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-sm text-gray-700 shadow-sm hover:bg-gray-100 hover:text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150']) }}>
    @if ($ikon)
        <x-ikon :nama="$ikon" />
    @endif
    {{ $slot }}
</button>
