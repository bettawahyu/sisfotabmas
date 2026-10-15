@props(['nilai'])
<label class="flex items-center gap-2 text-sm text-gray-700">
    <input type="hidden" name="is_active" value="0">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $nilai)) class="rounded border-gray-300">
    Aktif (nonaktifkan bila tidak dipakai lagi; data lama tetap utuh)
</label>
