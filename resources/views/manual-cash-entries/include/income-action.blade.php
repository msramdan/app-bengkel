@php $id = $model->id; @endphp
<div class="d-flex gap-1 justify-content-end">
    <button type="button" class="btn btn-action btn-action-view" data-action="show-entry" data-id="{{ $id }}" title="Detail">
        <i class="bi bi-eye"></i>
    </button>
    @can('manual income cancel')
        @if ($model->isCompleted())
            <button type="button" class="btn btn-action btn-action-delete" data-action="cancel-entry" data-id="{{ $id }}" title="Batalkan">
                <i class="bi bi-x-lg"></i>
            </button>
        @endif
    @endcan
</div>
