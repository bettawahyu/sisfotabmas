@php use App\Services\AlurKarya; @endphp
<nav class="mt-2 flex gap-4 text-sm">
    @foreach (AlurKarya::LABEL as $slug => $label)
        <a href="{{ route("karya.{$slug}.index") }}" class="{{ $slug === $jenis ? 'font-semibold text-gray-900 border-b-2 border-indigo-500' : 'text-indigo-700 hover:underline' }}">{{ $label }}</a>
    @endforeach
</nav>
