<div class="d-flex align-items-center justify-content-end gap-1">
    @can('purchase view')
        <button type="button" class="btn btn-action btn-action-view" data-action="show-purchase" data-id="{{ $id }}" title="Detail">
            <i class="fa fa-eye"></i>
        </button>
    @endcan
    @if ($status === 'completed')
        @can('purchase edit')
            <a href="{{ route('purchases.edit', $id) }}" class="btn btn-action btn-action-edit" title="Edit Pembelian">
                <i class="fa fa-pencil-alt"></i>
            </a>
        @endcan
        @can('purchase delete')
            <button type="button" class="btn btn-action btn-action-delete" data-action="cancel-purchase" data-id="{{ $id }}" data-no="{{ $purchase_no }}" title="Batalkan Pembelian">
                <i class="fa fa-trash-alt"></i>
            </button>
        @endcan
    @endif
</div>
