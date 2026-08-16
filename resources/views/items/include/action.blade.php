<td>
    <div class="d-flex align-items-center justify-content-end gap-1">
        @can('item view')
            <button type="button" class="btn btn-action btn-action-view" data-action="show" data-id="{{ $model->id }}" title="Detail">
                <i class="fa fa-eye"></i>
            </button>
        @endcan
        @can('item edit')
            <button type="button" class="btn btn-action btn-action-edit" data-action="edit" data-id="{{ $model->id }}" title="Edit">
                <i class="fa fa-pencil-alt"></i>
            </button>
        @endcan
        @can('item delete')
            <button type="button" class="btn btn-action btn-action-delete" data-action="delete" data-id="{{ $model->id }}" data-stock="{{ (int) $model->stock }}" title="Hapus">
                <i class="fa fa-trash-alt"></i>
            </button>
        @endcan
    </div>
</td>
