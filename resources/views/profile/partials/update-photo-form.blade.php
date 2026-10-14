<section x-data="{ pratinjau: null }">
    <p class="text-sm text-gray-600">Foto tampil di menu samping dan bilah atas. Format JPG, PNG, atau WebP, paling besar 2 MB. Tanpa foto, inisial nama yang ditampilkan.</p>

    <div class="mt-5 flex items-center gap-4">
        <template x-if="pratinjau">
            <img :src="pratinjau" alt="Pratinjau foto" class="h-20 w-20 rounded-full object-cover">
        </template>
        <template x-if="! pratinjau">
            <x-avatar :user="$user" ukuran="h-20 w-20 text-xl" />
        </template>
        @if ($user->foto_profil)
            <form method="post" action="{{ route('profile.foto.destroy') }}">
                @csrf
                @method('delete')
                <button class="inline-flex items-center gap-1.5 text-sm font-medium text-red-600 hover:text-red-700"><x-ikon nama="trash-2" /> Hapus foto</button>
            </form>
        @endif
    </div>

    <form method="post" action="{{ route('profile.foto.update') }}" enctype="multipart/form-data" class="mt-5 space-y-4">
        @csrf
        <label for="foto" class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-md border-2 border-dashed border-gray-300 px-4 py-6 text-center text-sm text-gray-500 hover:border-indigo-600 hover:text-indigo-600">
            <x-ikon nama="upload-cloud" class="h-6 w-6" />
            <span>Pilih foto baru</span>
            <input id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" required
                   @change="pratinjau = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null">
        </label>
        <x-input-error :messages="$errors->get('foto')" />

        <div class="flex items-center gap-4">
            <x-primary-button ikon="save">Simpan foto</x-primary-button>
            @if (in_array(session('status'), ['foto-updated', 'foto-deleted'], true))
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)" class="text-sm text-gray-600">
                    {{ session('status') === 'foto-updated' ? 'Foto disimpan.' : 'Foto dihapus.' }}
                </p>
            @endif
        </div>
    </form>
</section>
