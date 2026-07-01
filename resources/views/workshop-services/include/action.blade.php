@can('workshop service edit')
    <button type="button" class="btn btn-sm btn-outline-primary" data-action="edit" data-id="{{ $id }}" title="Edit">
        <i class="bi bi-pencil"></i>
    </button>
@endcan
@can('workshop service view')
    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="show" data-id="{{ $id }}" title="Detail">
        <i class="bi bi-eye"></i>
    </button>
@endcan
@can('workshop service delete')
    <button type="button" class="btn btn-sm btn-outline-danger" data-action="delete" data-id="{{ $id }}" title="Hapus">
        <i class="bi bi-trash"></i>
    </button>
@endcan
