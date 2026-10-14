@props(['karya'])
@php($slug = \App\Services\AlurKarya::slug($karya))
<p {{ $attributes->merge(['class' => 'text-gray-700']) }}>
    {{ \App\Services\AlurKarya::LABEL[$slug] }}: <span class="font-medium">{{ $karya->judul }}</span>
    <span class="text-gray-500">· {{ $karya->namaJenis() }} · {{ $karya->namaStatus() }} · {{ $karya->namaStatusVerifikasi() }}</span>
</p>
