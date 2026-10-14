<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Notifikasi</h2>
            @if (Auth::user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('notifikasi.baca-semua') }}">
                    @csrf
                    <x-secondary-button type="submit" ikon="check-square">Tandai semua sudah dibaca</x-secondary-button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl px-4 sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <ul class="divide-y divide-gray-100">
                    @forelse ($notifikasi as $n)
                        <li class="py-3 flex items-baseline justify-between gap-4">
                            <div>
                                <a href="{{ route('notifikasi.buka', $n->id) }}" class="text-sm {{ $n->read_at ? 'text-gray-600' : 'font-medium text-gray-900' }} hover:underline">{{ $n->data['judul'] ?? 'Notifikasi' }}</a>
                                @if (! empty($n->data['pesan']))
                                    <p class="mt-1 text-sm text-gray-600">{{ $n->data['pesan'] }}</p>
                                @endif
                            </div>
                            <span class="shrink-0 text-xs text-gray-500">{{ $n->created_at->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li class="py-3 text-sm text-gray-600">Belum ada notifikasi.</li>
                    @endforelse
                </ul>
                <div class="mt-4">{{ $notifikasi->links() }}</div>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
