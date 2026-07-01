@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @include('layouts.partials.page-hero', [
        'items' => [
            ['label' => 'Dashboard'],
        ],
        'title' => 'Dashboard',
        'subtitle' => brand_name() . ' — ' . config('branding.tagline'),
    ])

    <section class="section">
            {{-- Stat ring cards (style referensi panel) --}}
            <div class="row g-3 mb-3">
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card d-flex align-items-center gap-3">
                        <div class="stat-ring" style="--pct: 12">
                            <div class="stat-ring-inner">0</div>
                        </div>
                        <div>
                            <div class="stat-label">Servis Hari Ini</div>
                            <div class="stat-value accent">0</div>
                            <div class="stat-meta">Normal</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card d-flex align-items-center gap-3">
                        <div class="stat-ring" style="--pct: 25">
                            <div class="stat-ring-inner">0</div>
                        </div>
                        <div>
                            <div class="stat-label">Antrian</div>
                            <div class="stat-value accent">0</div>
                            <div class="stat-meta">Menunggu</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card d-flex align-items-center gap-3">
                        <div class="stat-ring" style="--pct: 48">
                            <div class="stat-ring-inner">0</div>
                        </div>
                        <div>
                            <div class="stat-label">Pelanggan</div>
                            <div class="stat-value accent">0</div>
                            <div class="stat-meta">Terdaftar</div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-xl-3">
                    <div class="stat-card d-flex align-items-center gap-3">
                        <div class="stat-ring" style="--pct: 65">
                            <div class="stat-ring-inner"><small>Rp</small></div>
                        </div>
                        <div>
                            <div class="stat-label">Pendapatan Bulan Ini</div>
                            <div class="stat-value accent">0</div>
                            <div class="stat-meta"><span class="text-accent">Target</span> —</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Overview row --}}
            <div class="row g-3 mb-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">Overview</div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-6 col-md-3">
                                    <div class="overview-card">
                                        <div class="overview-icon"><i class="bi bi-wrench-adjustable"></i></div>
                                        <div class="overview-title">Servis</div>
                                        <div class="overview-stat">Aktif: <strong>0</strong></div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="overview-card">
                                        <div class="overview-icon"><i class="bi bi-people"></i></div>
                                        <div class="overview-title">Pelanggan</div>
                                        <div class="overview-stat">Total: <strong>0</strong></div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="overview-card">
                                        <div class="overview-icon"><i class="bi bi-box-seam"></i></div>
                                        <div class="overview-title">Sparepart</div>
                                        <div class="overview-stat">Stok: <strong>0</strong></div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="overview-card">
                                        <div class="overview-icon"><i class="bi bi-person-badge"></i></div>
                                        <div class="overview-title">User</div>
                                        <div class="overview-stat">Aktif: <strong>{{ \App\Models\User::count() }}</strong></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <i class="bi bi-info-circle me-1"></i> Informasi Sistem
                        </div>
                        <div class="card-body">
                            <p class="mb-2">
                                Aplikasi <strong class="text-accent">{{ brand_name() }}</strong> siap digunakan.
                            </p>
                            <p class="text-muted mb-0" style="font-size:0.875rem;">
                                Login sebagai <strong>{{ auth()->user()->name }}</strong>
                                ({{ auth()->user()->roles->pluck('name')->join(', ') }}).
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
@endsection
