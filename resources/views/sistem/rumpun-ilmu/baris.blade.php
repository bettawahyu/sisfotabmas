@php
    use App\Models\RumpunIlmu;

    $tingkatAnak = array_search($node->tingkat, RumpunIlmu::TINGKAT_INDUK, true);
@endphp
<tr class="border-b border-gray-100 last:border-0 {{ $kedalaman === 0 ? 'bg-gray-50 font-medium' : '' }}">
    <td class="py-2 pe-4">{{ $node->kode }}</td>
    <td class="py-2 pe-4" style="padding-left: {{ $kedalaman * 1.5 }}rem">
        <span class="text-xs uppercase tracking-wide text-gray-500">{{ RumpunIlmu::TINGKAT[$node->tingkat] }}</span>
        {{ $node->nama }}
    </td>
    <td class="py-2 pe-4 text-gray-600">
        {{ $node->tingkat === RumpunIlmu::CABANG ? $node->proposal_count.' proposal' : $node->turunan_count.' turunan' }}
    </td>
    <td class="py-2 pe-4"><x-status-aktif :aktif="$node->is_active" /></td>
    <td class="py-2 text-end whitespace-nowrap">
        @if ($tingkatAnak)
            <a href="{{ route('sistem.rumpun-ilmu.create', ['induk' => $node->id]) }}" class="me-3 text-indigo-700 hover:underline">Tambah {{ strtolower(RumpunIlmu::TINGKAT[$tingkatAnak]) }}</a>
        @endif
        <a href="{{ route('sistem.rumpun-ilmu.edit', $node) }}" class="text-indigo-700 hover:underline">Ubah</a>
    </td>
</tr>
