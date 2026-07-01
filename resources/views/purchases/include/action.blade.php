<div class="d-flex align-items-center justify-content-end gap-1">
    @can('purchase view')
        <button type="button" class="btn btn-action btn-action-view" data-action="show-purchase" data-id="{{ $id }}" title="Detail">
            <i class="fa fa-eye"></i>
        </button>
    @endcan
</div>
