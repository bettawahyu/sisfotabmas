@props(['nama'])
<i data-feather="{{ $nama }}" {{ $attributes->merge(['class' => 'h-4 w-4 shrink-0']) }} aria-hidden="true"></i>
