@props(['value', 'ikon' => null])

<label {{ $attributes->merge(['class' => 'flex items-center gap-1.5 font-medium text-sm text-gray-700']) }}>
    @if ($ikon)
        <x-ikon :nama="$ikon" class="h-3.5 w-3.5 text-gray-500" />
    @endif
    {{ $value ?? $slot }}
</label>
