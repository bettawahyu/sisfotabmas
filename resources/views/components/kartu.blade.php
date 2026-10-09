@props(['judul' => null])
<section {{ $attributes->merge(['class' => 'bg-white shadow-sm sm:rounded-lg p-6']) }}>
    @if ($judul)
        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $judul }}</h3>
    @endif
    {{ $slot }}
</section>
