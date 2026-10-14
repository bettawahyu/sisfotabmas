@props(['active' => false, 'ikon'])

<a {{ $attributes->class(['sidebar-link', 'aktif' => $active]) }} @if ($active) aria-current="page" @endif>
    <x-ikon :nama="$ikon" />
    <span class="truncate">{{ $slot }}</span>
</a>
