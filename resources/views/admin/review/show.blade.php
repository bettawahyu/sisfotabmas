@php
    use App\Models\Penilaian;
@endphp
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Reviewer: {{ $proposal->judul }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ $proposal->periode->nama }} · Ketua {{ $proposal->ketua->namaLengkap() }}{{ $proposal->ketua->prodi ? ' · '.$proposal->ketua->prodi->nama : '' }}</p>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">
            <x-pesan />

            @if ($kriteria->isEmpty())
                <div class="rounded-md bg-yellow-50 p-4 text-sm text-yellow-800">
                    Periode ini belum punya kriteria penilaian seleksi. <a href="{{ route('admin.periode.edit', $proposal->periode) }}" class="font-medium underline">Isi kriteria di periode hibah</a> sebelum menugaskan reviewer.
                </div>
            @endif

            @foreach (range(1, Penilaian::JUMLAH_REVIEWER) as $urutan)
                @php($pen = $slot->get($urutan))
                <x-kartu ikon="user-check" :judul="'Reviewer '.$urutan">
                    @if ($pen)
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <p class="text-sm"><span class="font-medium">{{ $pen->reviewer->name }}</span>
                                <span class="text-gray-500">· {{ $pen->terkirim() ? 'dikirim '.$pen->dinilai_at->translatedFormat('d M Y H:i') : 'belum dikirim' }}</span></p>
                            @if ($pen->terkirim())
                                <p class="text-lg font-semibold">{{ number_format($pen->nilai_total, 2, ',', '.') }}</p>
                            @endif
                        </div>
                        @if ($peringatan->has($pen->reviewer_id))
                            <p class="mt-2 text-sm text-amber-700">Perhatian: {{ $peringatan->get($pen->reviewer_id) }}</p>
                        @endif

                        @if ($pen->terkirim())
                            <table class="mt-4 min-w-full text-sm">
                                <thead><tr class="text-left text-gray-500 border-b"><th class="py-2 pe-4 font-medium">Kriteria</th><th class="py-2 pe-4 font-medium text-end">Bobot</th><th class="py-2 font-medium text-end">Skor</th></tr></thead>
                                <tbody>
                                    @foreach ($kriteria as $k)
                                        <tr class="border-b border-gray-100 last:border-0"><td class="py-2 pe-4">{{ $k->nama }}</td><td class="py-2 pe-4 text-end">{{ $k->bobot }}</td><td class="py-2 text-end">{{ $pen->nilai->firstWhere('kriteria_id', $k->id)?->skor ?? '-' }} / {{ $k->skala_maks }}</td></tr>
                                    @endforeach
                                </tbody>
                            </table>
                            <dl class="mt-4 grid gap-3 text-sm">
                                <div><dt class="text-gray-500">Rekomendasi</dt><dd>{{ $pen->namaRekomendasi() }}{{ $pen->rekomendasi_dana ? ' · dana Rp '.number_format($pen->rekomendasi_dana, 0, ',', '.') : '' }}</dd></div>
                                <div><dt class="text-gray-500">Komentar untuk pengusul</dt><dd class="whitespace-pre-line">{{ $pen->komentar_untuk_pengusul }}</dd></div>
                                <div><dt class="text-gray-500">Catatan internal untuk LPPM</dt><dd class="whitespace-pre-line">{{ $pen->catatan_internal ?: '-' }}</dd></div>
                            </dl>
                            <form method="POST" action="{{ route('admin.review.buka', [$proposal, $pen]) }}" class="mt-4 flex flex-wrap gap-3">
                                @csrf
                                <x-text-input name="alasan" placeholder="Alasan membuka kembali" class="flex-1" required />
                                <x-secondary-button type="submit" ikon="unlock">Buka kembali</x-secondary-button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.review.destroy', [$proposal, $pen]) }}" data-konfirmasi="Batalkan penugasan reviewer ini?" class="mt-3">
                                @csrf
                                @method('DELETE')
                                <button class="text-sm text-red-700 hover:underline">Batalkan penugasan</button>
                            </form>
                        @endif
                    @endif

                    @if (! $pen?->terkirim())
                        <form method="POST" action="{{ route('admin.review.store', $proposal) }}" class="mt-4 flex flex-wrap gap-3">
                            @csrf
                            <input type="hidden" name="urutan" value="{{ $urutan }}">
                            <x-select-input name="reviewer_id" class="flex-1" required>
                                <option value="">Pilih reviewer</option>
                                @foreach ($reviewer as $r)
                                    <option value="{{ $r->id }}" @selected($pen?->reviewer_id === $r->id)>{{ $r->name }}{{ $r->dosen?->prodi ? ' · '.$r->dosen->prodi->nama : '' }}{{ $peringatan->has($r->id) ? ' (prodi sama dengan ketua)' : '' }}</option>
                                @endforeach
                            </x-select-input>
                            <x-primary-button ikon="user-check">{{ $pen ? 'Ganti reviewer' : 'Tugaskan' }}</x-primary-button>
                        </form>
                    @endif
                </x-kartu>
            @endforeach

            @php($terkirim = $slot->filter->terkirim())
            @if ($terkirim->count() === Penilaian::JUMLAH_REVIEWER)
                <x-kartu>
                    <p class="text-sm text-gray-600">Nilai akhir (rata-rata dua reviewer)</p>
                    <p class="text-2xl font-semibold">{{ number_format($terkirim->avg('nilai_total'), 2, ',', '.') }}</p>
                </x-kartu>
            @endif
        </div>
    </div>
</x-app-layout>
