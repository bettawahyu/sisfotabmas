@props(['user', 'ukuran' => 'h-10 w-10 text-sm'])
@if ($user->foto_profil)
    <img src="{{ route('foto-profil.show', $user) }}?v={{ $user->updated_at?->timestamp }}" alt="Foto {{ $user->name }}" {{ $attributes->merge(['class' => "$ukuran shrink-0 rounded-full object-cover"]) }}>
@else
    <span {{ $attributes->merge(['class' => "$ukuran inline-flex shrink-0 items-center justify-center rounded-full bg-indigo-600 font-semibold text-white"]) }} aria-hidden="true">{{ $user->inisial() }}</span>
@endif
