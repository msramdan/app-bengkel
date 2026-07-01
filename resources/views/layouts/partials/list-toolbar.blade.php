@if (! empty($createRoute) && (empty($createPermission) || auth()->user()->can($createPermission)))
    <div class="list-toolbar">
        <div class="list-toolbar-hint">
            <i class="bi bi-info-circle"></i>
            <span>{{ $hint ?? 'Gunakan tombol di kanan untuk menambah data baru.' }}</span>
        </div>
        <a href="{{ $createRoute }}" class="btn btn-primary btn-add">
            <i class="bi bi-plus-lg"></i> {{ $createLabel ?? 'Tambah' }}
        </a>
    </div>
@endif
