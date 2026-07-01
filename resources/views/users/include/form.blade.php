<div class="form-panel form-panel-clean">
    <div class="form-panel-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Nama Lengkap</label>
                <input type="text" name="name" id="name"
                    class="form-control form-control-clean @error('name') is-invalid @enderror"
                    value="{{ old('name', $user->name ?? '') }}" required autofocus
                    placeholder="Masukkan nama">
                @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">Alamat Email</label>
                <input type="email" name="email" id="email"
                    class="form-control form-control-clean @error('email') is-invalid @enderror"
                    value="{{ old('email', $user->email ?? '') }}" required
                    placeholder="nama@email.com">
                @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="password" class="form-label">Password</label>
                <input type="password" name="password" id="password"
                    class="form-control form-control-clean @error('password') is-invalid @enderror"
                    {{ empty($user) ? 'required' : '' }}
                    value="" placeholder="Min. 8 karakter" autocomplete="new-password">
                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @isset($user)
                    <div class="form-hint-sm">Kosongkan jika tidak diubah.</div>
                @endisset
            </div>
            <div class="col-md-6">
                <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation"
                    class="form-control form-control-clean"
                    {{ empty($user) ? 'required' : '' }}
                    value="" placeholder="Ulangi password" autocomplete="new-password">
            </div>
            <div class="col-md-6">
                <label for="role" class="form-label">Role</label>
                <select name="role" id="role"
                    class="form-select form-control-clean @error('role') is-invalid @enderror" required>
                    <option value="">-- Pilih Role --</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}"
                            @selected(old('role', isset($user) ? $user->roles->first()?->id : '') == $role->id)>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
                @error('role')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>
</div>
