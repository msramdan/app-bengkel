<div class="d-flex align-items-center justify-content-end gap-1">
    @can('transaction view')
        <button type="button" class="btn btn-action btn-action-view" data-action="show-tx" data-id="{{ $id }}" title="Detail">
            <i class="fa fa-eye"></i>
        </button>
        @if ($status === 'completed')
            <a href="{{ route('transactions.invoice', $id) }}" target="_blank" class="btn btn-action btn-action-edit" title="Cetak Invoice">
                <i class="fa fa-print"></i>
            </a>
        @endif
    @endcan
    @if ($status === 'completed')
        @can('transaction edit')
            <a href="{{ route('transactions.edit', $id) }}" class="btn btn-action btn-action-edit" title="Edit Transaksi">
                <i class="fa fa-pencil-alt"></i>
            </a>
        @endcan
        @can('transaction delete')
            <button type="button" class="btn btn-action btn-action-delete" data-action="cancel-tx" data-id="{{ $id }}" data-no="{{ $transaction_no }}" title="Batalkan Transaksi">
                <i class="fa fa-trash-alt"></i>
            </button>
        @endcan
    @endif
</div>
