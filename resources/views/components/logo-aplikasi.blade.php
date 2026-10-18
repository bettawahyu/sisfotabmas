{{-- The uploaded logo, or the built-in SVG. "gelap" picks the variant for a dark background. --}}
@props(['gelap' => false])
@php($url = app(\App\Services\IdentitasAplikasi::class)->url($gelap ? 'logo-gelap' : 'logo'))
@if ($url)
    <img src="{{ $url }}" alt="Logo {{ config('app.name') }}" {{ $attributes->merge(['class' => 'shrink-0 object-contain']) }}>
@else
    <x-application-logo {{ $attributes->merge(['class' => 'shrink-0']) }} />
@endif
