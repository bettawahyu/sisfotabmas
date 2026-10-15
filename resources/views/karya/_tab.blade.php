@php
    use App\Services\AlurKarya;

    $tabKarya = collect(AlurKarya::LABEL)->map(fn ($label, $slug) => [
        'label' => $label,
        'ikon' => AlurKarya::IKON[$slug],
        'href' => route('karya.'.$slug.'.index'),
        'aktif' => $slug === $jenis,
    ])->values()->all();
@endphp
<x-tab class="mb-4" :daftar="$tabKarya" />
