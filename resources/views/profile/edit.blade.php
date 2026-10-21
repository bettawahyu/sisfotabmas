<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Akun</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl px-4 sm:px-6 lg:px-8 space-y-6">
            <x-kartu judul="Akun login" ikon="user">
                <dl class="grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-xs text-gray-500">Email</dt>
                        <dd class="mt-1 break-all font-medium text-gray-800">{{ $user->email }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-gray-500">Peran</dt>
                        <dd class="mt-1 text-gray-800">{{ $user->roles->map(fn ($r) => \App\Models\Role::ALL[$r->kode] ?? $r->nama)->implode(', ') ?: '-' }}</dd>
                    </div>
                </dl>
                <p class="mt-4 text-xs text-gray-500">
                    Email dan nama akun diubah oleh Admin Sistem.
                    @if ($user->hasRole(\App\Models\Role::DOSEN))
                        Nama dan data diri dosen diisi di menu <a href="{{ route('profil-dosen.edit') }}" class="text-indigo-700 hover:underline">Profil Dosen</a>.
                    @endif
                </p>
            </x-kartu>

            {{-- Lecturers change their photo on the lecturer profile. --}}
            @unless ($user->hasRole(\App\Models\Role::DOSEN))
                <x-kartu judul="Foto profil" ikon="camera">
                    @include('profile.partials.update-photo-form')
                </x-kartu>
            @endunless

            <x-kartu :judul="__('Update Password')" ikon="lock">
                @include('profile.partials.update-password-form')
            </x-kartu>
        </div>
    </div>
</x-app-layout>
