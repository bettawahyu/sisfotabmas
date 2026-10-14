@if (session('status'))
    <div class="mb-4 flex items-start gap-3 rounded-md border border-green-200 bg-green-50 p-4 text-sm text-green-800" role="status">
        <x-ikon nama="check-circle" class="mt-0.5" />
        <span>{{ session('status') }}</span>
    </div>
@endif
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
