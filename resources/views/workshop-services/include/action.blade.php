<div class="d-flex align-items-center justify-content-end gap-1">
    @can('workshop service view')
        <button type="button" class="btn btn-action btn-action-view" data-action="show" data-id="{{ $id }}" title="Detail">
            <i class="fa fa-eye"></i>
        </button>
    @endcan
    @can('workshop service edit')
        <button type="button" class="btn btn-action btn-action-edit" data-action="edit" data-id="{{ $id }}" title="Edit">
            <i class="fa fa-pencil-alt"></i>
        </button>
    @endcan
    @can('workshop service delete')
        <button type="button" class="btn btn-action btn-action-delete" data-action="delete" data-id="{{ $id }}" title="Hapus">
            <i class="fa fa-trash-alt"></i>
        </button>
    @endcan
</div>
