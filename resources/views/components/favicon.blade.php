@php($url = app(\App\Services\IdentitasAplikasi::class)->url('favicon'))
<link rel="icon" href="{{ $url ?? asset('favicon.ico') }}">
