<td>
    <div class="d-flex align-items-center justify-content-end gap-1">
        @can('stock out view')
            <button type="button" class="btn btn-action btn-action-view" data-action="show-batch"
                data-batch="{{ $model->batch_no }}" title="Detail">
                <i class="fa fa-eye"></i>
            </button>
        @endcan
    </div>
</td>
