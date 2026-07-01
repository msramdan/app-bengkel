<aside class="form-layout-aside">
    @isset($user)
        <div class="form-aside-card">
            <div class="form-aside-card-banner"></div>
            <div class="form-aside-card-body text-center">
                <div class="form-aside-avatar">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                <h3 class="form-aside-name">{{ $user->name }}</h3>
                <p class="form-aside-email">{{ $user->email }}</p>
                <span class="profile-role-badge">
                    {{ $user->roles->pluck('name')->first() ?: 'Belum ada role' }}
                </span>
            </div>
            <ul class="form-aside-meta list-unstyled">
                <li>
                    <i class="bi bi-calendar3"></i>
                    <div>
                        <span class="meta-label">Terdaftar</span>
                        <span class="meta-value">{{ $user->created_at?->format('d/m/Y H:i') ?? '-' }}</span>
                    </div>
                </li>
                <li>
                    <i class="bi bi-pencil-square"></i>
                    <div>
                        <span class="meta-label">Terakhir diubah</span>
                        <span class="meta-value">{{ $user->updated_at?->format('d/m/Y H:i') ?? '-' }}</span>
                    </div>
                </li>
            </ul>
        </div>
    @else
        <div class="form-aside-card form-aside-card-flat">
            <div class="form-aside-card-body">
                <h3 class="form-aside-title"><i class="bi bi-lightbulb me-2"></i>Panduan</h3>
                <ul class="form-aside-tips list-unstyled">
                    <li><i class="bi bi-check2"></i> Gunakan email aktif untuk notifikasi sistem.</li>
                    <li><i class="bi bi-check2"></i> Password minimal 8 karakter.</li>
                    <li><i class="bi bi-check2"></i> Pilih role sesuai tugas pengguna.</li>
                </ul>
            </div>
        </div>
    @endisset

    <div class="form-aside-card form-aside-card-flat mt-3">
        <div class="form-aside-card-body">
            <h3 class="form-aside-title"><i class="bi bi-shield me-2"></i>Role Tersedia</h3>
            <ul class="form-aside-roles list-unstyled">
                @foreach ($roles as $role)
                    <li>
                        <span class="role-dot"></span>
                        {{ $role->name }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</aside>
