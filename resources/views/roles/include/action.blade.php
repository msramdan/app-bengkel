<td>
    <div class="d-flex align-items-center gap-1">
        @can('role view')
            <a href="{{ route('roles.show', $model) }}" class="btn btn-action btn-action-view" title="Detail">
                <i class="fa fa-eye"></i>
            </a>
        @endcan
        @can('role edit')
            <a href="{{ route('roles.edit', $model) }}" class="btn btn-action btn-action-edit" title="Edit">
                <i class="fa fa-pencil-alt"></i>
            </a>
        @endcan
        @can('role delete')
            @if ($model->name !== 'Super Admin')
                <form action="{{ route('roles.destroy', $model) }}" method="post" class="d-inline m-0"
                    onsubmit="return confirm('Hapus role ini?')">
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
