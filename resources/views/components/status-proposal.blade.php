@props(['proposal'])
@php
    $warna = match ($proposal->status) {
        \App\Enums\StatusProposal::Dikembalikan => 'bg-yellow-100 text-yellow-800',
        \App\Enums\StatusProposal::TidakDidanai => 'bg-red-100 text-red-800',
        \App\Enums\StatusProposal::Selesai, \App\Enums\StatusProposal::Disetujui, \App\Enums\StatusProposal::Didanai => 'bg-green-100 text-green-800',
        default => 'bg-gray-100 text-gray-800',
    };
    $label = $proposal->menungguPengesahan() ? 'Menunggu pengesahan' : $proposal->status->label();
@endphp
<span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $warna }}">{{ $label }}</span>
