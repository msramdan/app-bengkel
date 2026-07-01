<td>
    <div class="d-flex align-items-center gap-1">
        @can('user view')
            <a href="{{ route('users.show', $model) }}" class="btn btn-action btn-action-view" title="Detail">
                <i class="fa fa-eye"></i>
            </a>
        @endcan
        @can('user edit')
            <a href="{{ route('users.edit', $model) }}" class="btn btn-action btn-action-edit" title="Edit">
                <i class="fa fa-pencil-alt"></i>
            </a>
        @endcan
        @can('user delete')
            @if ($model->id !== auth()->id() && $model->id !== 1)
                <form action="{{ route('users.destroy', $model) }}" method="post" class="d-inline m-0"
                    onsubmit="return confirm('Hapus user ini?')">
                    @csrf
                    @method('delete')
                    <button type="submit" class="btn btn-action btn-action-delete" title="Hapus">
                        <i class="fa fa-trash-alt"></i>
                    </button>
                </form>
            @endif
        @endcan
    </div>
</td>
