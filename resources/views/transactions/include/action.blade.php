@can('transaction view')
    <a href="{{ route('transactions.invoice', $id) }}" target="_blank" class="btn btn-sm btn-outline-primary" title="Cetak Invoice">
        <i class="bi bi-printer"></i>
    </a>
    <button type="button" class="btn btn-sm btn-outline-secondary" data-action="show-tx" data-id="{{ $id }}" title="Detail">
        <i class="bi bi-eye"></i>
    </button>
@endcan
