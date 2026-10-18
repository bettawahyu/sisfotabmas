{{-- Success and other flashes show as floating notices (components/notifikasi); errors also stay here, next to the form. --}}
@if ($errors->any())
    <div class="mb-4 flex items-start gap-3 rounded-md border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
        <x-ikon nama="alert-triangle" class="mt-0.5" />
        <ul class="list-disc ps-5 space-y-1">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
