<!DOCTYPE html>
<html lang="id" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <script>
        (function () {
            try {
                var theme = localStorage.getItem('atha-theme');
                if (theme === 'light' || theme === 'dark') {
                    document.documentElement.setAttribute('data-bs-theme', theme);
                }
            } catch (e) {}
        })();
    </script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - {{ brand_name() }}</title>
    <meta name="theme-color" content="#8B1538">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/atha-admin.css') }}">

    @stack('css')
</head>
<body>
<div class="admin-layout" id="admin-layout">
<script>try{if(localStorage.getItem('atha-sidebar-collapsed')==='1'&&window.innerWidth>=992){document.getElementById('admin-layout').classList.add('is-sidebar-collapsed');document.querySelectorAll('.sidebar-nav .collapse.show').forEach(function(el){el.classList.remove('show');el.style.height=''})}}catch(e){}</script>
    @include('layouts.sidebar')

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="d-flex align-items-center justify-content-between w-100 gap-3">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-icon d-lg-none" id="btn-sidebar-toggle" aria-label="Buka menu">
                        <i class="bi bi-list fs-4"></i>
                    </button>
                    <button type="button" class="btn btn-icon d-none d-lg-inline-flex" id="btn-sidebar-collapse"
                        aria-label="Perkecil sidebar" title="Perkecil / perlebar sidebar">
                        <i class="bi bi-list"></i>
                    </button>
                </div>

                <div class="ms-auto d-flex align-items-center gap-2 gap-md-3">
                    <button type="button" class="btn btn-icon" id="btn-theme-toggle"
                        aria-label="Ganti tema" title="Ganti tema terang/gelap">
                        <i class="bi bi-sun-fill theme-icon-light"></i>
                        <i class="bi bi-moon-stars-fill theme-icon-dark"></i>
                    </button>

                    @auth
                        <div class="dropdown">
                            <button class="btn btn-profile dropdown-toggle" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="btn-profile-avatar">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </span>
                                <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end border-0 mt-2">
                                <li class="dropdown-header">
                                    <div class="fw-semibold">{{ auth()->user()->name }}</div>
                                    <small class="text-body-secondary">
                                        {{ auth()->user()->roles->pluck('name')->join(', ') ?: 'User' }}
                                    </small>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                        <i class="bi bi-person-gear me-2"></i> Edit Profil
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="bi bi-box-arrow-right me-2"></i> Keluar
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @endauth
                </div>
            </div>
        </header>

        <main class="admin-content">
            @include('layouts.flash')
