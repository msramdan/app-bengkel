<aside class="form-layout-aside">
    @isset($role)
        <div class="form-aside-card">
            <div class="form-aside-card-banner"></div>
            <div class="form-aside-card-body text-center">
                <div class="form-aside-avatar form-aside-avatar-role">
                    <i class="bi bi-shield-check"></i>
                </div>
                <h3 class="form-aside-name">{{ $role->name }}</h3>
                <span class="profile-role-badge">Role</span>
            </div>
            <ul class="form-aside-meta list-unstyled">
                <li>
                    <i class="bi bi-people"></i>
                    <div>
                        <span class="meta-label">Jumlah user</span>
                        <span class="meta-value">{{ $role->users()->count() }} pengguna</span>
                    </div>
                </li>
                <li>
                    <i class="bi bi-key"></i>
                    <div>
                        <span class="meta-label">Permission aktif</span>
                        <span class="meta-value">{{ $role->permissions->count() }} hak akses</span>
                    </div>
                </li>
            </ul>
        </div>
    @else
        <div class="form-aside-card form-aside-card-flat">
            <div class="form-aside-card-body">
                <h3 class="form-aside-title"><i class="bi bi-lightbulb me-2"></i>Panduan Role</h3>
                <ul class="form-aside-tips list-unstyled">
                    <li><i class="bi bi-check2"></i> Beri nama role yang jelas, misalnya Kasir atau Mekanik.</li>
                    <li><i class="bi bi-check2"></i> Centang hanya permission yang dibutuhkan.</li>
                    <li><i class="bi bi-check2"></i> Kurangi akses untuk keamanan data.</li>
                </ul>
            </div>
        </div>
    @endisset

    <div class="form-aside-card form-aside-card-flat mt-3">
        <div class="form-aside-card-body">
            <h3 class="form-aside-title"><i class="bi bi-grid me-2"></i>Grup Permission</h3>
            <ul class="form-aside-roles list-unstyled">
                @foreach (\App\Support\PermissionGroups::all() as $group)
                    <li>
                        <span class="role-dot"></span>
                        {{ $group['group'] }}
                        <small class="text-muted">({{ count($group['access']) }})</small>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</aside>
