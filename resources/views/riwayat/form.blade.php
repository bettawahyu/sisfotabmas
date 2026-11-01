@php
    use App\Enums\TipeAnggota;
    use App\Models\Mitra;
    use App\Models\RiwayatKegiatan;
    use App\Services\AlurRiwayat;

    $pkm = $jenis === 'pkm';
    $foto = $riwayat->exists ? $riwayat->berkas->where('kategori', RiwayatKegiatan::BERKAS_FOTO) : collect();
    $berkasTunggal = fn (string $kategori) => $riwayat->exists ? $riwayat->berkas->firstWhere('kategori', $kategori) : null;
    $kembali = $lppm ? route('riwayat-dosen.index', $jenis) : route('riwayat.index', $jenis);
    $ketuaAwal = old('ketua', collect($anggota)->search(fn ($a) => ($a['peran'] ?? null) === RiwayatKegiatan::KETUA));
    $pilihanMitra = $mitra->map(fn ($m) => ['id' => $m->id, 'nama' => $m->nama, 'bidang' => $m->bidang, 'usulan' => $m->status === Mitra::USULAN])->values();
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $riwayat->exists ? 'Ubah' : 'Catat' }} {{ $pkm ? 'Pengabdian kepada Masyarakat' : 'Penelitian' }}</h2>
        <p class="mt-1 text-sm text-gray-600"><a href="{{ $kembali }}" class="text-indigo-700 hover:underline">Kembali ke daftar</a></p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl px-4 sm:px-6 lg:px-8"
            x-data="{
                konfirmasi: false,
                ganda: @js(session('ganda', [])),
                mitra: @js($pilihanMitra),
                terpilih: @js($mitraTerpilih),
                calon: '',
                baru: { nama: '', bidang: '', alamat: '', kontak: '' },
                galat: [],
                tambah() { const id = Number(this.calon); if (id && ! this.terpilih.includes(id)) this.terpilih.push(id); this.calon = '' },
                lepas(id) { this.terpilih = this.terpilih.filter(t => t !== id) },
                namaMitra(id) { const m = this.mitra.find(m => m.id === id); return m ? m.nama + (m.bidang ? ' (' + m.bidang + ')' : '') : '' },
                usulan(id) { return this.mitra.find(m => m.id === id)?.usulan },
                async kirim(form) {
                    if (this.konfirmasi) return form.submit();
                    const isian = new FormData();
                    isian.append('judul', form.judul.value);
                    isian.append('tahun_mulai', form.tahun_mulai.value);
                    @if ($riwayat->exists) isian.append('kecuali', @js($riwayat->id)); @endif
                    try {
                        const jawab = await fetch(@js(route('riwayat.cek-ganda', $jenis)), { method: 'POST', body: isian,
                            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } });
                        this.ganda = jawab.ok ? (await jawab.json()).ganda : [];
                    } catch (e) { this.ganda = [] }
                    if (this.ganda.length) return this.$dispatch('open-modal', 'ganda');
                    form.submit();
                },
                async simpanMitra() {
                    this.galat = [];
                    const jawab = await fetch(@js(route('mitra.usulan')), { method: 'POST', body: JSON.stringify(this.baru),
                        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } });
                    const isi = await jawab.json();
                    if (! jawab.ok) { this.galat = Object.values(isi.errors ?? { umum: [isi.message] }).flat(); return }
                    if (! this.mitra.some(m => m.id === isi.id)) this.mitra.push({ id: isi.id, nama: isi.nama, bidang: isi.bidang, usulan: isi.status === @js(Mitra::USULAN) });
                    if (! this.terpilih.includes(isi.id)) this.terpilih.push(isi.id);
                    this.baru = { nama: '', bidang: '', alamat: '', kontak: '' };
                    this.$dispatch('close-modal', 'mitra-baru');
                },
            }"
            x-init="if (ganda.length) $nextTick(() => $dispatch('open-modal', 'ganda'))">
            <x-pesan />
            <form method="POST" enctype="multipart/form-data" x-ref="form" x-on:submit.prevent="kirim($refs.form)"
                action="{{ $riwayat->exists ? route('riwayat.update', [$jenis, $riwayat]) : route('riwayat.store', $jenis) }}" class="space-y-6">
                @csrf
                @if ($riwayat->exists)
                    @method('PUT')
                @endif
                <input type="hidden" name="abaikan_ganda" x-bind:value="konfirmasi ? 1 : 0">
                <p class="text-sm text-gray-600">
                    @if ($lppm)
                        Data yang Anda simpan langsung tervalidasi dan tampil di halaman riwayat dosen yang tercantum.
                    @else
                        Setelah disimpan, data menunggu verifikasi LPPM atau operator.
                    @endif
                </p>

                <x-kartu ikon="file-text" judul="Kegiatan">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input-label for="judul" value="Judul" ikon="type" />
                            <x-text-input id="judul" name="judul" class="mt-1 block w-full" :value="old('judul', $riwayat->judul)" required />
                        </div>
                        <div>
                            <x-input-label for="tahun_mulai" value="Tahun mulai" ikon="calendar" />
                            <x-text-input id="tahun_mulai" name="tahun_mulai" type="number" min="1950" class="mt-1 block w-full" :value="old('tahun_mulai', $riwayat->tahun_mulai)" required />
                        </div>
                        <div>
                            <x-input-label for="tahun_selesai" value="Tahun selesai (kosongkan bila satu tahun)" ikon="calendar" />
                            <x-text-input id="tahun_selesai" name="tahun_selesai" type="number" min="1950" class="mt-1 block w-full" :value="old('tahun_selesai', $riwayat->tahun_selesai)" />
                        </div>
                        <div>
                            <x-input-label for="status_kegiatan" value="Status kegiatan" ikon="flag" />
                            <x-select-input id="status_kegiatan" name="status_kegiatan" class="mt-1 block w-full">
                                @foreach (RiwayatKegiatan::STATUS_KEGIATAN as $kode => $nama)
                                    <option value="{{ $kode }}" @selected(old('status_kegiatan', $riwayat->status_kegiatan) === $kode)>{{ $nama }}</option>
                                @endforeach
                            </x-select-input>
                        </div>
                        <div>
                            <x-input-label for="bidang_fokus_id" value="Bidang fokus" ikon="target" />
                            <x-select-input id="bidang_fokus_id" name="bidang_fokus_id" class="mt-1 block w-full">
                                <option value="">-</option>
                                @foreach ($bidangFokus as $b)
                                    <option value="{{ $b->id }}" @selected((string) old('bidang_fokus_id', $riwayat->bidang_fokus_id) === (string) $b->id)>{{ $b->nama }}</option>
                                @endforeach
                            </x-select-input>
                        </div>
                        <div class="sm:col-span-2">
                            <x-input-label for="ringkasan" value="Ringkasan" ikon="align-left" />
                            <textarea id="ringkasan" name="ringkasan" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('ringkasan', $riwayat->ringkasan) }}</textarea>
                        </div>
                    </div>
                </x-kartu>

                <x-kartu ikon="dollar-sign" judul="Pendanaan">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <x-input-label for="sumber_dana" value="Sumber dana" ikon="briefcase" />
                            <x-select-input id="sumber_dana" name="sumber_dana" class="mt-1 block w-full">
                                @foreach (RiwayatKegiatan::SUMBER_DANA as $kode => $nama)
                                    <option value="{{ $kode }}" @selected(old('sumber_dana', $riwayat->sumber_dana) === $kode)>{{ $nama }}</option>
                                @endforeach
                            </x-select-input>
                        </div>
                        <div>
                            <x-input-label for="pemberi_dana" value="Nama pemberi dana" ikon="home" />
                            <x-text-input id="pemberi_dana" name="pemberi_dana" class="mt-1 block w-full" :value="old('pemberi_dana', $riwayat->pemberi_dana)" placeholder="DRTPM, Pemkab Sleman, PT ..." />
                        </div>
                        <div>
                            <x-input-label for="skema" value="Program atau skema" ikon="layers" />
                            <x-text-input id="skema" name="skema" class="mt-1 block w-full" :value="old('skema', $riwayat->skema)" />
                        </div>
                        <div>
                            <x-input-label for="nilai_dana" value="Nilai dana (Rp)" ikon="dollar-sign" />
                            <x-text-input id="nilai_dana" name="nilai_dana" type="number" min="0" class="mt-1 block w-full" :value="old('nilai_dana', $riwayat->nilai_dana)" />
                        </div>
                        <div>
                            <x-input-label for="no_kontrak" value="Nomor kontrak atau SK" ikon="hash" />
                            <x-text-input id="no_kontrak" name="no_kontrak" class="mt-1 block w-full" :value="old('no_kontrak', $riwayat->no_kontrak)" />
                        </div>
                    </div>
                </x-kartu>

                <x-kartu ikon="users" judul="Tim">
                    <p class="mb-4 text-sm text-gray-600">
                        Pilih dosen bila orangnya dosen di sini; data ini otomatis tampil di riwayat setiap dosen yang tercantum.
                        Untuk mahasiswa isi NIM, untuk pihak luar isi institusinya. Tandai satu ketua.
                        @unless ($lppm) Nama Anda harus tercantum. @endunless
                    </p>
                    <div class="space-y-3">
                        @foreach ($anggota as $i => $a)
                            <div class="grid gap-3 sm:grid-cols-12 items-center" x-data="{ tipe: @js(old("anggota.$i.tipe", $a['tipe'] ?? TipeAnggota::Dosen->value)) }">
                                <span class="sm:col-span-1 text-sm text-gray-500">{{ $i + 1 }}.</span>
                                <x-select-input name="anggota[{{ $i }}][tipe]" x-model="tipe" class="sm:col-span-2" aria-label="Jenis anggota {{ $i + 1 }}">
                                    @foreach (TipeAnggota::cases() as $t)
                                        <option value="{{ $t->value }}">{{ $t->label() }}</option>
                                    @endforeach
                                </x-select-input>
                                <x-select-input name="anggota[{{ $i }}][dosen_id]" class="sm:col-span-5" x-show="tipe === 'dosen'" x-bind:disabled="tipe !== 'dosen'" aria-label="Dosen urutan {{ $i + 1 }}">
                                    <option value="">Pilih dosen</option>
                                    @foreach ($dosen as $d)
                                        <option value="{{ $d->id }}" @selected((string) old("anggota.$i.dosen_id", $a['dosen_id'] ?? '') === (string) $d->id)>{{ $d->namaLengkap() }}</option>
                                    @endforeach
                                </x-select-input>
                                <x-text-input name="anggota[{{ $i }}][nama]" placeholder="Nama" class="sm:col-span-3" x-show="tipe !== 'dosen'" x-bind:disabled="tipe === 'dosen'" :value="old('anggota.'.$i.'.nama', $a['nama'] ?? null)" />
                                <x-text-input name="anggota[{{ $i }}][keterangan]" x-bind:placeholder="tipe === 'mahasiswa' ? 'NIM' : 'Institusi'" class="sm:col-span-2" x-show="tipe !== 'dosen'" x-bind:disabled="tipe === 'dosen'" :value="old('anggota.'.$i.'.keterangan', $a['keterangan'] ?? null)" />
                                <label class="sm:col-span-2 flex items-center gap-2 text-sm text-gray-700">
                                    <input type="radio" name="ketua" value="{{ $i }}" @checked((string) $ketuaAwal === (string) $i) class="border-gray-300">
                                    Ketua
                                </label>
                            </div>
                        @endforeach
                    </div>
                </x-kartu>

                <x-kartu ikon="briefcase" judul="Mitra dan lokasi">
                    <p class="mb-4 text-sm text-gray-600">
                        {{ $pkm ? 'Kegiatan PkM wajib mencantumkan minimal satu mitra.' : 'Mitra bersifat opsional untuk penelitian.' }}
                        Bila mitra belum ada di daftar, tambahkan lewat tombol Mitra baru. @unless ($lppm) LPPM akan memeriksa kelayakannya untuk masuk data master; mitra itu tetap tercatat di kegiatan Anda. @endunless
                    </p>
                    <template x-for="id in terpilih" :key="id">
                        <input type="hidden" name="mitra[]" :value="id">
                    </template>
                    <div class="mb-3 flex flex-wrap gap-2">
                        <template x-for="id in terpilih" :key="id">
                            <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-sm text-indigo-800">
                                <span x-text="namaMitra(id)"></span>
                                <span x-show="usulan(id)" class="text-xs text-gray-500">(usulan)</span>
                                <button type="button" x-on:click="lepas(id)" class="text-indigo-600 hover:text-red-700" aria-label="Lepas mitra">&times;</button>
                            </span>
                        </template>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <x-select-input x-model="calon" class="flex-1 min-w-48" aria-label="Pilih mitra">
                            <option value="">Pilih mitra dari daftar</option>
                            <template x-for="m in mitra.filter(m => ! terpilih.includes(m.id))" :key="m.id">
                                <option :value="m.id" x-text="m.nama + (m.bidang ? ' (' + m.bidang + ')' : '')"></option>
                            </template>
                        </x-select-input>
                        <x-secondary-button type="button" ikon="plus" x-on:click="tambah()">Tambahkan</x-secondary-button>
                        <x-secondary-button type="button" ikon="user-plus" x-on:click="$dispatch('open-modal', 'mitra-baru')">Mitra baru</x-secondary-button>
                    </div>

                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        @include('riwayat._wilayah', ['nilai' => $riwayat->kabupaten_kota_kode])
                        <div class="sm:col-span-2">
                            <x-input-label for="alamat_lokasi" value="Alamat lokasi pelaksanaan (desa, kecamatan)" ikon="map-pin" />
                            <x-text-input id="alamat_lokasi" name="alamat_lokasi" class="mt-1 block w-full" :value="old('alamat_lokasi', $riwayat->alamat_lokasi)" />
                        </div>
                    </div>
                </x-kartu>

                @if ($pkm)
                    <x-kartu ikon="camera" judul="Dokumentasi dan dampak">
                        <p class="mb-4 text-sm text-gray-600">Untuk kegiatan yang sudah selesai: wajib foto dokumentasi atau tautan video YouTube, surat keterangan mitra, dan catatan dampak. Publikasi media massa opsional.</p>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <x-input-label for="jumlah_sasaran" value="Jumlah sasaran (orang)" ikon="users" />
                                <x-text-input id="jumlah_sasaran" name="jumlah_sasaran" type="number" min="0" class="mt-1 block w-full" :value="old('jumlah_sasaran', $riwayat->jumlah_sasaran)" />
                            </div>
                            <div>
                                <x-input-label for="tautan_video" value="Tautan video YouTube" ikon="youtube" />
                                <x-text-input id="tautan_video" name="tautan_video" type="url" class="mt-1 block w-full" :value="old('tautan_video', $riwayat->tautan_video)" placeholder="https://youtu.be/..." />
                            </div>
                            <div class="sm:col-span-2">
                                <x-input-label for="foto" value="Foto dokumentasi (JPG/PNG, maks. 5 MB per foto)" ikon="image" />
                                <input id="foto" name="foto[]" type="file" multiple accept="image/jpeg,image/png,image/webp" class="mt-1 block w-full text-sm">
                                @if ($foto->isNotEmpty())
                                    <div class="mt-2 space-y-1 text-sm">
                                        @foreach ($foto as $f)
                                            <label class="flex items-center gap-2">
                                                <input type="checkbox" name="hapus_foto[]" value="{{ $f->id }}" class="rounded border-gray-300">
                                                Hapus <a href="{{ route('riwayat.berkas', $f) }}" class="text-indigo-700 hover:underline">{{ $f->nama_asli }}</a>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                            <div class="sm:col-span-2">
                                <x-input-label for="surat_mitra" value="Surat keterangan mitra (PDF atau gambar)" ikon="file" />
                                <input id="surat_mitra" name="surat_mitra" type="file" accept="application/pdf,image/jpeg,image/png" class="mt-1 block w-full text-sm">
                                @if ($b = $berkasTunggal(RiwayatKegiatan::BERKAS_SURAT_MITRA))
                                    <p class="mt-1 text-xs text-gray-500">Sudah ada: <a href="{{ route('riwayat.berkas', $b) }}" class="text-indigo-700 hover:underline">{{ $b->nama_asli }}</a>. Unggah lagi untuk mengganti.</p>
                                @endif
                            </div>
                            <div class="sm:col-span-2">
                                <x-input-label for="catatan_dampak" value="Catatan dampak bagi mitra" ikon="trending-up" />
                                <textarea id="catatan_dampak" name="catatan_dampak" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Kondisi mitra sebelum dan sesudah kegiatan">{{ old('catatan_dampak', $riwayat->catatan_dampak) }}</textarea>
                            </div>
                            <div class="sm:col-span-2">
                                <x-input-label for="tautan_media" value="Publikasi media massa (opsional, satu tautan per baris)" ikon="link" />
                                <textarea id="tautan_media" name="tautan_media" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('tautan_media', $riwayat->tautan_media) }}</textarea>
                            </div>
                        </div>
                    </x-kartu>
                @endif

                <x-kartu ikon="paperclip" judul="Berkas pendukung">
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach (['bukti' => [RiwayatKegiatan::BERKAS_BUKTI, 'SK, surat tugas, atau kontrak (PDF)'], 'laporan' => [RiwayatKegiatan::BERKAS_LAPORAN, 'Laporan akhir (PDF, wajib bila kegiatan selesai)']] as $nama => [$kategori, $label])
                            <div>
                                <x-input-label :for="$nama" :value="$label" ikon="file" />
                                <input id="{{ $nama }}" name="{{ $nama }}" type="file" accept="application/pdf" class="mt-1 block w-full text-sm">
                                @if ($b = $berkasTunggal($kategori))
                                    <p class="mt-1 text-xs text-gray-500">Sudah ada: <a href="{{ route('riwayat.berkas', $b) }}" class="text-indigo-700 hover:underline">{{ $b->nama_asli }}</a>. Unggah lagi untuk mengganti.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </x-kartu>

                <x-kartu ikon="award" judul="Luaran terkait">
                    <p class="mb-4 text-sm text-gray-600">
                        Opsional. Tautkan publikasi, HKI, atau paten hasil kegiatan ini dari menu Publikasi &amp; KI dosen yang tercantum. Luaran tidak menggantikan laporan akhir.
                        @if ($lppm && ! $riwayat->exists) Pilihan luaran muncul setelah data disimpan dan timnya tercatat. @endif
                    </p>
                    @php($adaKarya = collect($pilihanKarya)->flatten()->isNotEmpty())
                    @if ($adaKarya)
                        <div class="space-y-2 text-sm">
                            @foreach ($pilihanKarya as $slug => $daftarKarya)
                                @foreach ($daftarKarya as $k)
                                    <label class="flex items-start gap-2">
                                        <input type="checkbox" name="luaran[]" value="{{ $slug }}:{{ $k->id }}" @checked(in_array($slug.':'.$k->id, $luaranTerpilih, true)) class="mt-0.5 rounded border-gray-300">
                                        <x-karya-tertaut :karya="$k" />
                                    </label>
                                @endforeach
                            @endforeach
                        </div>
                    @elseif ($riwayat->exists || ! $lppm)
                        <p class="text-sm text-gray-500">Belum ada publikasi, HKI, atau paten. Tambahkan dulu di <a href="{{ route('karya.publikasi.index') }}" class="text-indigo-700 hover:underline">Publikasi &amp; KI</a>.</p>
                    @endif
                </x-kartu>

                <x-primary-button ikon="save">Simpan</x-primary-button>
            </form>

            <x-modal name="ganda" maxWidth="lg" focusable>
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-800">Kemungkinan data ganda</h3>
                    <p class="mt-1 text-sm text-gray-600">Kegiatan dengan judul mirip pada tahun yang berdekatan sudah tercatat. Bila ini kegiatan yang sama, cukup satu orang yang mencatat dan mencantumkan anggota timnya.</p>
                    <ul class="mt-4 space-y-2 text-sm">
                        <template x-for="g in ganda">
                            <li class="rounded-md border border-gray-200 p-3">
                                <p class="font-medium text-gray-800" x-text="g.judul"></p>
                                <p class="text-gray-600" x-text="g.keterangan"></p>
                            </li>
                        </template>
                    </ul>
                    @if (session('ganda'))
                        <p class="mt-3 text-xs text-gray-500">Berkas yang tadi dipilih perlu dipilih ulang sebelum menyimpan.</p>
                    @endif
                    <div class="mt-6 flex justify-end gap-3">
                        <x-secondary-button type="button" x-on:click="$dispatch('close')">Batal</x-secondary-button>
                        <x-primary-button type="button" x-on:click="konfirmasi = true; $nextTick(() => kirim($refs.form))">Tetap simpan</x-primary-button>
                    </div>
                </div>
            </x-modal>

            <x-modal name="mitra-baru" maxWidth="lg" focusable>
                <div class="p-6 space-y-4">
                    <h3 class="text-lg font-semibold text-gray-800">Mitra baru</h3>
                    <template x-if="galat.length">
                        <ul class="list-disc ps-5 text-sm text-red-700"><template x-for="g in galat"><li x-text="g"></li></template></ul>
                    </template>
                    <div>
                        <x-input-label for="mitra_nama" value="Nama mitra" ikon="briefcase" />
                        <x-text-input id="mitra_nama" class="mt-1 block w-full" x-model="baru.nama" />
                    </div>
                    <div>
                        <x-input-label for="mitra_bidang" value="Bergerak di bidang" ikon="tag" />
                        <x-text-input id="mitra_bidang" class="mt-1 block w-full" x-model="baru.bidang" placeholder="UMKM kuliner, sekolah dasar, kelompok tani..." />
                    </div>
                    <div>
                        <x-input-label for="mitra_alamat" value="Alamat" ikon="map-pin" />
                        <x-text-input id="mitra_alamat" class="mt-1 block w-full" x-model="baru.alamat" />
                    </div>
                    <div>
                        <x-input-label for="mitra_kontak" value="Telepon atau email" ikon="phone" />
                        <x-text-input id="mitra_kontak" class="mt-1 block w-full" x-model="baru.kontak" />
                    </div>
                    <div class="flex justify-end gap-3">
                        <x-secondary-button type="button" x-on:click="$dispatch('close')">Batal</x-secondary-button>
                        <x-primary-button type="button" x-on:click="simpanMitra()">Simpan mitra</x-primary-button>
                    </div>
                </div>
            </x-modal>
        </div>
    </div>
</x-app-layout>
