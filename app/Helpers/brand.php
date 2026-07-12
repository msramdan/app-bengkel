<?php

if (! function_exists('brand_name')) {
    function brand_name(): string
    {
        try {
            return app(\App\Services\SettingService::class)->getString('app_name', (string) config('branding.name'));
        } catch (\Throwable) {
            return (string) config('branding.name');
        }
    }
}

if (! function_exists('brand_tagline')) {
    function brand_tagline(): string
    {
        try {
            return app(\App\Services\SettingService::class)->getString('app_tagline', (string) config('branding.tagline'));
        } catch (\Throwable) {
            return (string) config('branding.tagline');
        }
    }
}

if (! function_exists('app_description')) {
    function app_description(): string
    {
        try {
            return app(\App\Services\SettingService::class)->getString('app_description', (string) config('branding.description', ''));
        } catch (\Throwable) {
            return (string) config('branding.description', '');
        }
    }
}

if (! function_exists('brand_address')) {
    function brand_address(): string
    {
        try {
            return trim(app(\App\Services\SettingService::class)->getString('company_address'));
        } catch (\Throwable) {
            return '';
        }
    }
}

if (! function_exists('brand_whatsapp')) {
    function brand_whatsapp(): string
    {
        try {
            return trim(app(\App\Services\SettingService::class)->getString('company_whatsapp'));
        } catch (\Throwable) {
            return '';
        }
    }
}

if (! function_exists('brand_logo_url')) {
    function brand_logo_url(): string
    {
        try {
            $path = trim(app(\App\Services\SettingService::class)->getString('company_logo'));

            if ($path !== '' && \Illuminate\Support\Facades\Storage::disk('public')->exists($path)) {
                return \Illuminate\Support\Facades\Storage::disk('public')->url($path);
            }
        } catch (\Throwable) {
            // fallback ke logo default
        }

        return asset((string) config('branding.logo', 'logo.png'));
    }
}

if (! function_exists('brand_has_custom_logo')) {
    function brand_has_custom_logo(): bool
    {
        try {
            $path = trim(app(\App\Services\SettingService::class)->getString('company_logo'));

            return $path !== '' && \Illuminate\Support\Facades\Storage::disk('public')->exists($path);
        } catch (\Throwable) {
            return false;
        }
    }
}

if (! function_exists('user_can_menu')) {
    function user_can_menu(?string $permission): bool
    {
        if ($permission === null || $permission === '') {
            return true;
        }

        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (! \Spatie\Permission\Models\Permission::query()->where('name', $permission)->exists()) {
            return false;
        }

        return $user->can($permission);
    }
}

if (! function_exists('is_active_menu')) {
    function is_active_menu(string|array $routes): string
    {
        $routes = (array) $routes;

        foreach ($routes as $route) {
            if (request()->routeIs($route) || request()->is($route)) {
                return ' active';
            }
        }

        return '';
    }
}

if (! function_exists('is_active_submenu')) {
    function is_active_submenu(array $submenus): string
    {
        $routes = collect($submenus)->pluck('route')->filter()->all();

        return is_active_menu($routes);
    }
}
