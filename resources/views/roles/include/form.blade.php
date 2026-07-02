<div class="form-panel form-panel-clean">
    <div class="form-panel-body">
        <div class="row g-3 mb-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Nama Role</label>
                <input type="text" name="name" id="name"
                    class="form-control form-control-clean @error('name') is-invalid @enderror"
                    value="{{ old('name', $role->name ?? '') }}" required autofocus
                    placeholder="Contoh: Kasir, Mekanik"
                    {{ isset($role) && $role->name === 'Super Admin' ? 'readonly' : '' }}>
                @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>

        <label class="form-label d-block mb-2">Permission</label>
        @error('permissions')<div class="text-danger small mb-3">{{ $message }}</div>@enderror
        <div class="row g-3">
            @foreach (config('permissions') as $group)
                <div class="col-md-6 col-lg-4">
                    <div class="perm-group">
                        <h6 class="perm-group-title">{{ $group['group'] }}</h6>
                        @foreach ($group['access'] as $access)
                            <div class="form-check perm-check">
                                <input class="form-check-input" type="checkbox" name="permissions[]"
                                    id="perm_{{ str()->slug($access) }}" value="{{ $access }}"
                                    @checked(isset($role) ? $role->permissions->contains('name', $access) : in_array($access, old('permissions', [])))>
                                <label class="form-check-label" for="perm_{{ str()->slug($access) }}">
                                    {{ ucwords($access) }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
