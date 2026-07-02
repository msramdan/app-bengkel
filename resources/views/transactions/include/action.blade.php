<div class="d-flex align-items-center justify-content-end gap-1">
    @can('transaction create')
        @if ($status === 'held')
            <a href="{{ route('transactions.create', ['held' => $id]) }}" class="btn btn-action btn-action-edit" title="Lanjutkan Open Order">
                <i class="fa fa-play"></i>
            </a>
            <button type="button" class="btn btn-action btn-action-delete" data-action="cancel-held" data-id="{{ $id }}" title="Batalkan Open Order">
                <i class="fa fa-times"></i>
            </button>
        @endif
    @endcan
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
</div>
