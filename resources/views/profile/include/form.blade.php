<div class="form-panel form-panel-clean">
    <div class="form-panel-body">
        <div class="row g-3">
            <div class="col-md-6">
                <label for="name" class="form-label">Nama Lengkap</label>
                <div class="input-icon-wrap">
                    <i class="bi bi-person input-icon"></i>
                    <input type="text" name="name" id="name"
                        class="form-control form-control-icon @error('name') is-invalid @enderror"
                        value="{{ old('name', $user->name) }}" required autofocus
                        placeholder="Masukkan nama">
                </div>
                @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">Alamat Email</label>
                <div class="input-icon-wrap">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" name="email" id="email"
                        class="form-control form-control-icon @error('email') is-invalid @enderror"
                        value="{{ old('email', $user->email) }}" required
                        placeholder="nama@email.com">
                </div>
                @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label class="form-label">Role</label>
                <div class="input-icon-wrap">
                    <i class="bi bi-shield-lock input-icon"></i>
                    <input type="text" class="form-control form-control-icon" readonly disabled
                        value="{{ $user->roles->pluck('name')->join(', ') ?: '-' }}">
                </div>
            </div>
            <div class="col-md-6">
                <label for="current_password" class="form-label">Password Saat Ini</label>
                <div class="input-icon-wrap">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" name="current_password" id="current_password"
                        class="form-control form-control-icon @error('current_password') is-invalid @enderror"
                        autocomplete="new-password" value="" placeholder="Ketik password saat ini">
                </div>
                @error('current_password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
            <div class="col-md-6">
                <label for="password" class="form-label">Password Baru</label>
                <div class="input-icon-wrap">
                    <i class="bi bi-lock-fill input-icon"></i>
                    <input type="password" name="password" id="password"
                        class="form-control form-control-icon @error('password') is-invalid @enderror"
                        autocomplete="new-password" value="" placeholder="Min. 8 karakter">
                </div>
                @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                <div class="form-hint-sm">Kosongkan jika tidak diubah.</div>
            </div>
            <div class="col-md-6">
                <label for="password_confirmation" class="form-label">Konfirmasi Password Baru</label>
                <div class="input-icon-wrap">
                    <i class="bi bi-lock-fill input-icon"></i>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                        class="form-control form-control-icon" autocomplete="new-password"
                        value="" placeholder="Ulangi password baru">
                </div>
            </div>
        </div>
    </div>
</div>
