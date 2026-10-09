@if ($daftar->isEmpty())
    <p class="text-sm text-gray-600">Belum ada.</p>
@else
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="py-2 pe-4 font-medium">Judul</th>
                    <th class="py-2 pe-4 font-medium">Periode</th>
                    @isset($tampilKetua)
                        <th class="py-2 pe-4 font-medium">Ketua</th>
                    @endisset
                    <th class="py-2 pe-4 font-medium">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($daftar as $proposal)
                    <tr class="border-b border-gray-100 last:border-0">
                        <td class="py-2 pe-4"><a href="{{ route('proposal.show', $proposal) }}" class="text-indigo-700 hover:underline">{{ $proposal->judul }}</a></td>
                        <td class="py-2 pe-4 text-gray-600">{{ $proposal->periode->nama }}</td>
                        @isset($tampilKetua)
                            <td class="py-2 pe-4 text-gray-600">{{ $proposal->ketua->namaLengkap() }}</td>
                        @endisset
                        <td class="py-2 pe-4"><x-status-proposal :proposal="$proposal" /></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
