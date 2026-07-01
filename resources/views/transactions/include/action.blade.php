<div class="d-flex align-items-center justify-content-end gap-1">
    @can('transaction view')
        <button type="button" class="btn btn-action btn-action-view" data-action="show-tx" data-id="{{ $id }}" title="Detail">
            <i class="fa fa-eye"></i>
        </button>
        <a href="{{ route('transactions.invoice', $id) }}" target="_blank" class="btn btn-action btn-action-edit" title="Cetak Invoice">
            <i class="fa fa-print"></i>
        </a>
    @endcan
</div>
