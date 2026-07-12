@extends('layouts.app')

@section('title', 'Pengaturan Aplikasi')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard', 'url' => route('dashboard')],
            ['label' => 'Pengaturan Aplikasi'],
        ],
        'title' => 'Pengaturan Aplikasi',
        'subtitle' => 'Identitas bengkel, kontak, logo, gateway WhatsApp HiWA, dan pengingat servis.',
    ])

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form action="{{ route('settings.update') }}" method="POST" class="form-page-inner" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="data-panel h-100">
                    <div class="data-panel-head">
                        <h2 class="data-panel-title"><i class="bi bi-building me-1"></i> Identitas Bengkel</h2>
                    </div>
                    <div class="data-panel-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Bengkel <span class="text-danger">*</span></label>
                            <input type="text" name="app_name" class="form-control form-control-clean @error('app_name') is-invalid @enderror"
                                value="{{ old('app_name', $settings['app_name']) }}" required>
                            @error('app_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-hint-sm">Ditampilkan di sidebar, login, nota, dan judul halaman.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tagline / Subjudul</label>
                            <input type="text" name="app_tagline" class="form-control form-control-clean"
                                value="{{ old('app_tagline', $settings['app_tagline']) }}" placeholder="Sistem Manajemen Bengkel">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat Bengkel</label>
                            <textarea name="company_address" class="form-control form-control-clean @error('company_address') is-invalid @enderror" rows="2"
                                placeholder="Jl. Contoh No. 1, Kota">{{ old('company_address', $settings['company_address'] ?? '') }}</textarea>
                            @error('company_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-hint-sm">Tampil di nota penjualan / struk thermal.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">WhatsApp Bengkel</label>
                            <input type="text" name="company_whatsapp" class="form-control form-control-clean @error('company_whatsapp') is-invalid @enderror"
                                value="{{ old('company_whatsapp', $settings['company_whatsapp'] ?? '') }}"
                                placeholder="08xxxxxxxxxx">
                            @error('company_whatsapp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-hint-sm">Nomor kontak pelanggan (bukan token gateway HiWA).</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi Aplikasi</label>
                            <textarea name="app_description" class="form-control form-control-clean" rows="3">{{ old('app_description', $settings['app_description']) }}</textarea>
                        </div>
                        <div class="mb-0">
                            @include('layouts.partials.entity-photo-field', [
                                'label' => 'Logo Bengkel',
                                'placeholderIcon' => 'bi-shop',
                            ])
                            @error('photo')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                            <div class="form-hint-sm mt-1">Logo dipakai di halaman login, sidebar, dan nota (jika diisi).</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="data-panel h-100">
                    <div class="data-panel-head">
                        <h2 class="data-panel-title"><i class="bi bi-whatsapp me-1"></i> Gateway WhatsApp (HiWA)</h2>
                    </div>
                    <div class="data-panel-body">
                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" name="hiwa_enabled" value="1" class="form-check-input" id="hiwa_enabled"
                                @checked(old('hiwa_enabled', $settings['hiwa_enabled']))>
                            <label class="form-check-label" for="hiwa_enabled">Aktifkan integrasi HiWA</label>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Token Device</label>
                            <input type="password" name="hiwa_token_device" class="form-control form-control-clean"
                                placeholder="{{ $settings['hiwa_token_device_masked'] ? 'Token tersimpan — isi baru untuk mengganti' : 'Token dari perangkat HiWA' }}"
                                autocomplete="new-password">
                            @if ($settings['hiwa_token_device_masked'])
                                <div class="form-hint-sm">Token saat ini: {{ $settings['hiwa_token_device_masked'] }}</div>
                            @endif
                            <div class="form-hint-sm">Dari dashboard HiWA saat perangkat pertama kali terhubung.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="data-panel">
                    <div class="data-panel-head data-panel-head-row">
                        <h2 class="data-panel-title"><i class="bi bi-bell me-1"></i> Pengingat Servis Berkala</h2>
                        @can('settings edit')
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-run-reminders">
                                <i class="bi bi-play-circle"></i> Jalankan Sekarang
                            </button>
                        @endcan
                    </div>
                    <div class="data-panel-body">
                        <div class="form-check form-switch mb-3">
                            <input type="checkbox" name="oil_change_reminder_enabled" value="1" class="form-check-input" id="oil_change_reminder_enabled"
                                @checked(old('oil_change_reminder_enabled', $settings['oil_change_reminder_enabled']))>
                            <label class="form-check-label" for="oil_change_reminder_enabled">Aktifkan pengingat otomatis via WhatsApp</label>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Interval Pengingat <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" name="oil_change_reminder_months" class="form-control form-control-clean" min="1" max="24"
                                        value="{{ old('oil_change_reminder_months', $settings['oil_change_reminder_months']) }}" required>
                                    <span class="input-group-text">bulan</span>
                                </div>
                                <div class="form-hint-sm">Contoh: 2 atau 3 bulan setelah servis terakhir pelanggan.</div>
                            </div>
                            <div class="col-md-8">
                                @php
                                    $selectedServiceIds = collect(old('oil_change_workshop_service_ids', $settings['oil_change_workshop_service_ids'] ?? []))
                                        ->map(fn ($id) => (int) $id)
                                        ->all();
                                @endphp
                                <label class="form-label">Jasa Servis Pengingat</label>
                                <div class="border rounded p-3 reminder-service-list" style="max-height: 220px; overflow-y: auto;">
                                    @forelse ($workshopServices as $service)
                                        <div class="form-check">
                                            <input type="checkbox"
                                                class="form-check-input"
                                                name="oil_change_workshop_service_ids[]"
                                                value="{{ $service->id }}"
                                                id="reminder_service_{{ $service->id }}"
                                                @checked(in_array($service->id, $selectedServiceIds, true))>
                                            <label class="form-check-label" for="reminder_service_{{ $service->id }}">
                                                {{ $service->code }} — {{ $service->name }}
                                            </label>
                                        </div>
                                    @empty
                                        <p class="text-muted small mb-0">Belum ada jasa servis aktif. Tambahkan di menu Master Jasa.</p>
                                    @endforelse
                                </div>
                                <div class="form-hint-sm">Pilih satu atau lebih jasa. Kosongkan semua untuk otomatis mencocokkan nama jasa yang mengandung "oli".</div>
                            </div>
                        </div>
                        <p class="form-hint-sm mt-3 mb-0">
                            Pesan pengingat menggunakan template baku sistem. Isi pesan tidak perlu diatur manual.
                        </p>
                        <div id="run-reminders-result" class="small mt-3 text-muted"></div>
                        <p class="form-hint-sm mt-2 mb-0">
                            <i class="bi bi-clock me-1"></i> Scheduler harian: <code>php artisan reminders:oil-change</code> (jam 08:00).
                            Pastikan cron server menjalankan <code>php artisan schedule:run</code>.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        @include('layouts.partials.form-actions', [
            'backUrl' => route('dashboard'),
            'submitLabel' => 'Simpan Pengaturan',
        ])
    </form>
@endsection

@push('js')
    <script>
        @if (! empty($logoUrl))
            if (window.AthaEntityPhoto) {
                AthaEntityPhoto.setPreview(@json($logoUrl));
            }
        @endif

        $('#btn-run-reminders').on('click', function () {
            const $result = $('#run-reminders-result');
            $result.text('Memproses pengingat...');
            $.post({
                url: '{{ route('settings.run-reminders') }}',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'), Accept: 'application/json' },
            }).done(function (res) {
                $result.removeClass('text-danger').addClass('text-success').text(res.message);
            }).fail(function (xhr) {
                $result.removeClass('text-success').addClass('text-danger')
                    .text(xhr.responseJSON?.message || 'Gagal menjalankan pengingat.');
            });
        });
    </script>
@endpush
