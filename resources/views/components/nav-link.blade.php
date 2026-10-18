@props(['active' => false, 'ikon'])

<a {{ $attributes->class(['sidebar-link', 'aktif' => $active])->merge(['title' => trim(strip_tags((string) $slot))]) }} @if ($active) aria-current="page" @endif>
    <x-ikon :nama="$ikon" />
    <span class="sidebar-teks truncate">{{ $slot }}</span>
</a>
