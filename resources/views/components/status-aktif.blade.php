@props(['aktif'])
<span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $aktif ? 'bg-green-100 text-green-800' : 'bg-gray-200 text-gray-700' }}">{{ $aktif ? 'Aktif' : 'Nonaktif' }}</span>
