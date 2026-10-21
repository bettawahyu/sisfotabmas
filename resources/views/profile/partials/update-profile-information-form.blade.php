<section>
    <header>
        <p class="text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('Name')" ikon="user" />
            @if ($user->dosen)
                <p id="name" class="mt-1 block w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700">{{ $user->name }}</p>
                <p class="mt-1 text-xs text-gray-500">Nama dosen diubah di menu <a href="{{ route('profil-dosen.edit') }}" class="text-indigo-700 hover:underline">Kualifikasi Dosen</a>.</p>
            @else
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            @endif
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" ikon="mail" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-800">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-600">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <x-input-label for="no_telp" value="No. telepon" ikon="phone" />
                <x-text-input id="no_telp" name="no_telp" type="tel" class="mt-1 block w-full" :value="old('no_telp', $user->no_telp)" autocomplete="tel" />
                <x-input-error class="mt-2" :messages="$errors->get('no_telp')" />
            </div>
            <div>
                <x-input-label for="jenis_kelamin" value="Jenis kelamin" ikon="user" />
                <x-select-input id="jenis_kelamin" name="jenis_kelamin" class="mt-1 block w-full">
                    <option value="">-</option>
                    @foreach (\App\Models\User::JENIS_KELAMIN as $kode => $label)
                        <option value="{{ $kode }}" @selected(old('jenis_kelamin', $user->jenis_kelamin) === $kode)>{{ $label }}</option>
                    @endforeach
                </x-select-input>
                <x-input-error class="mt-2" :messages="$errors->get('jenis_kelamin')" />
            </div>
            <div>
                <x-input-label for="tempat_lahir" value="Tempat lahir" ikon="map-pin" />
                <x-text-input id="tempat_lahir" name="tempat_lahir" class="mt-1 block w-full" :value="old('tempat_lahir', $user->tempat_lahir)" />
                <x-input-error class="mt-2" :messages="$errors->get('tempat_lahir')" />
            </div>
            <div>
                <x-input-label for="tgl_lahir" value="Tanggal lahir" ikon="calendar" />
                <x-text-input id="tgl_lahir" name="tgl_lahir" type="date" class="mt-1 block w-full" :value="old('tgl_lahir', $user->tgl_lahir?->format('Y-m-d'))" />
                <x-input-error class="mt-2" :messages="$errors->get('tgl_lahir')" />
            </div>
            <div>
                <x-input-label for="status_pernikahan" value="Status pernikahan" ikon="heart" />
                <x-select-input id="status_pernikahan" name="status_pernikahan" class="mt-1 block w-full">
                    <option value="">-</option>
                    @foreach (\App\Models\User::STATUS_PERNIKAHAN as $kode => $label)
                        <option value="{{ $kode }}" @selected(old('status_pernikahan', $user->status_pernikahan) === $kode)>{{ $label }}</option>
                    @endforeach
                </x-select-input>
                <x-input-error class="mt-2" :messages="$errors->get('status_pernikahan')" />
            </div>
            <div class="sm:col-span-2">
                <x-input-label for="alamat" value="Alamat" ikon="home" />
                <textarea id="alamat" name="alamat" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('alamat', $user->alamat) }}</textarea>
                <x-input-error class="mt-2" :messages="$errors->get('alamat')" />
            </div>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button ikon="save">{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600"
                >{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>
