@extends('layouts.auth')

@section('title', 'Masuk')

@section('content')
    <div class="auth-page">
        <div class="auth-card">
            <div class="auth-brand">
                <div class="auth-brand-glow" aria-hidden="true"></div>
                <img src="{{ brand_logo_url() }}" alt="{{ brand_name() }}" class="auth-logo">
                <h1 class="auth-brand-name">{{ brand_name() }}</h1>
                <p class="auth-brand-tagline">{{ brand_tagline() }}</p>
            </div>

            <div class="auth-body">
                <div class="auth-intro">
                    <h2 class="auth-title">Masuk</h2>
                    <p class="auth-subtitle">Silakan login untuk melanjutkan ke panel admin.</p>
                </div>

                <form class="auth-form" method="POST" action="{{ route('login') }}">
                    @csrf

                    @if ($errors->any())
                        <div class="auth-alert auth-alert-danger" role="alert">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    @if (session('status'))
                        <div class="auth-alert auth-alert-success">{{ session('status') }}</div>
                    @endif

                    <div class="mb-3">
                        <label class="auth-label" for="email">Alamat Email</label>
                        <div class="auth-input-wrap">
                            <i class="bi bi-envelope auth-input-icon"></i>
                            <input type="email" class="auth-input @error('email') is-invalid @enderror"
                                name="email" id="email" placeholder="nama@email.com"
                                value="{{ old('email') }}" required autofocus autocomplete="email">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="auth-label" for="password">Kata Sandi</label>
                        <div class="auth-input-wrap">
                            <i class="bi bi-lock auth-input-icon"></i>
                            <input type="password" class="auth-input @error('password') is-invalid @enderror"
                                name="password" id="password" placeholder="Ketik kata sandi"
                                required autocomplete="current-password">
                            <button type="button" class="auth-toggle-pass" id="togglePassword" aria-label="Tampilkan sandi">
                                <i class="bi bi-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="auth-options">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember" name="remember"
                                {{ old('remember') ? 'checked' : '' }}>
                            <label class="form-check-label auth-check-label" for="remember">Ingat saya</label>
                        </div>
                    </div>

                    <button type="submit" class="auth-btn-submit">
                        <i class="bi bi-box-arrow-in-right"></i> Masuk
                    </button>
                </form>
            </div>
        </div>

        <p class="auth-footer">&copy; {{ date('Y') }} {{ brand_name() }}</p>
    </div>
@endsection

@push('js')
<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const input = document.getElementById('password');
        const icon = document.getElementById('togglePasswordIcon');
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        icon.classList.toggle('bi-eye', !isPassword);
        icon.classList.toggle('bi-eye-slash', isPassword);
    });
</script>
@endpush
