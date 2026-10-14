<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-6">
        <div class="px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- Profile header in the Dash UI style: cover band, photo, name and roles --}}
            <section class="overflow-hidden rounded-md bg-white shadow">
                <div class="h-28 bg-gradient-to-r from-indigo-600 to-indigo-800"></div>
                <div class="flex flex-wrap items-end gap-4 px-6 pb-6">
                    <x-avatar :user="$user" ukuran="h-24 w-24 text-2xl" class="-mt-12 border-4 border-white shadow" />
                    <div class="min-w-0 flex-1">
                        <h3 class="text-xl font-semibold text-gray-800">{{ $user->name }}</h3>
                        <p class="flex items-center gap-1.5 text-sm text-gray-500"><x-ikon nama="mail" class="h-3.5 w-3.5" /> {{ $user->email }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($user->roles as $peran)
                            <span class="inline-flex items-center gap-1 rounded-full bg-indigo-600/10 px-3 py-1 text-xs font-medium text-indigo-700"><x-ikon nama="tag" class="h-3 w-3" />{{ \App\Models\Role::ALL[$peran->kode] ?? $peran->nama }}</span>
                        @endforeach
                    </div>
                </div>
            </section>

            <div class="grid gap-6 lg:grid-cols-3">
                <x-kartu judul="Foto profil" ikon="camera" class="lg:col-span-1">
                    @include('profile.partials.update-photo-form')
                </x-kartu>

                <x-kartu :judul="__('Profile Information')" ikon="user" class="lg:col-span-2">
                    @include('profile.partials.update-profile-information-form')
                </x-kartu>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <x-kartu :judul="__('Update Password')" ikon="lock">
                    @include('profile.partials.update-password-form')
                </x-kartu>

                <x-kartu :judul="__('Delete Account')" ikon="alert-octagon">
                    @include('profile.partials.delete-user-form')
                </x-kartu>
            </div>
        </div>
    </div>
</x-app-layout>
