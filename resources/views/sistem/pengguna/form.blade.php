<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $pengguna->exists ? 'Ubah Pengguna' : 'Pengguna Baru' }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <x-pesan />
            <x-kartu>
                <form method="POST" action="{{ $pengguna->exists ? route('sistem.pengguna.update', $pengguna) : route('sistem.pengguna.store') }}" class="grid gap-4 sm:grid-cols-2">
                    @csrf
                    @if ($pengguna->exists)
                        @method('PUT')
                    @endif
                    <div>
                        <x-input-label for="name" value="Nama" />
                        <x-text-input id="name" name="name" class="mt-1 block w-full" :value="old('name', $pengguna->name)" required />
                    </div>
                    <div>
                        <x-input-label for="email" value="Email" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $pengguna->email)" required />
                    </div>
                    <div>
                        <x-input-label for="password" :value="$pengguna->exists ? 'Kata sandi baru (kosongkan bila tetap)' : 'Kata sandi'" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" :required="! $pengguna->exists" />
                    </div>
                    <div>
                        <x-input-label for="password_confirmation" value="Ulangi kata sandi" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                    </div>
                    <fieldset class="sm:col-span-2">
                        <legend class="text-sm font-medium text-gray-700">Peran</legend>
                        @php($dipilih = old('peran', $pengguna->exists ? $pengguna->roles->pluck('kode')->all() : []))
                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                            @foreach (\App\Models\Role::ALL as $kode => $nama)
                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                    <input type="checkbox" name="peran[]" value="{{ $kode }}" @checked(in_array($kode, $dipilih, true)) class="rounded border-gray-300">
                                    {{ $nama }}
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs text-gray-500">Unit kaprodi atau dekan untuk peran Pimpinan Unit diatur admin LPPM di menu Unit pimpinan.</p>
                    </fieldset>
                    <div class="sm:col-span-2"><x-primary-button>Simpan</x-primary-button></div>
                </form>
            </x-kartu>
        </div>
    </div>
</x-app-layout>
