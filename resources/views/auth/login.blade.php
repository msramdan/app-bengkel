@extends('layouts.auth')

@section('title', 'Masuk')

@section('content')
    <div class="login-form-container">
        <div class="form_container">
            <div class="form_header">
                <img src="{{ brand_logo_url() }}" alt="{{ brand_name() }}">
                <p class="brand-tagline mb-0">{{ config('branding.tagline') }}</p>
            </div>
            <form class="app-form" method="POST" action="{{ route('login') }}">
                @csrf

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <div class="mb-3">
                    <label class="form-label" for="email">Alamat Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                        name="email" id="email" placeholder="nama@email.com"
                        value="{{ old('email') }}" required autofocus autocomplete="email">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="password">Kata Sandi</label>
                    <div class="input-group">
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                            name="password" id="password" placeholder="Kata sandi"
                            required autocomplete="current-password">
                        <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                            <i class="fas fa-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember"
                            {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember">Ingat Saya</label>
                    </div>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-primary small">Lupa kata sandi?</a>
                    @endif
                </div>

                <button type="submit" class="btn btn-login w-100">Masuk</button>
            </form>
        </div>
    </div>
@endsection

@push('js')
<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const input = document.getElementById('password');
        const icon = document.getElementById('togglePasswordIcon');
        const isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        icon.classList.toggle('fa-eye', !isPassword);
        icon.classList.toggle('fa-eye-slash', isPassword);
    });
</script>
@endpush
