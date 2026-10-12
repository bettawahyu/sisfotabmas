<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in!") }}
                    <p class="mt-2 text-sm text-gray-600">
                        {{ __('Roles') }}: {{ Auth::user()->roles->pluck('nama')->join(', ') ?: '-' }}
                    </p>
                </div>
            </div>

            <x-kartu judul="Pengumuman terbaru">
                <ul class="divide-y divide-gray-100">
                    @forelse ($pengumuman as $p)
                        <li class="py-2 flex items-baseline justify-between gap-4">
                            <a href="{{ route('pengumuman.show', $p) }}" class="text-sm text-indigo-700 hover:underline">{{ $p->judul }}</a>
                            <span class="shrink-0 text-xs text-gray-500">{{ $p->terbit_at->translatedFormat('d M Y') }}</span>
                        </li>
                    @empty
                        <li class="py-2 text-sm text-gray-600">Belum ada pengumuman.</li>
                    @endforelse
                </ul>
                <a href="{{ route('pengumuman.index') }}" class="mt-3 inline-block text-sm text-indigo-700 hover:underline">Semua pengumuman</a>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
