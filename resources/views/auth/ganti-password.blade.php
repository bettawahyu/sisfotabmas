<x-guest-layout>
    <h1 class="text-lg font-semibold text-gray-800">Ganti kata sandi</h1>
    <p class="mt-2 text-sm text-gray-600">
        Kata sandi akun ini dibuat oleh orang lain. Ganti dengan kata sandi Anda sendiri sebelum memakai aplikasi.
    </p>

    @include('profile.partials.update-password-form')

    <form method="POST" action="{{ route('logout') }}" class="mt-6">
        @csrf
        <button type="submit" class="text-sm text-gray-600 underline hover:text-gray-900">{{ __('Log Out') }}</button>
    </form>
</x-guest-layout>
