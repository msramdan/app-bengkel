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

if (! function_exists('brand_logo_url')) {
    function brand_logo_url(): string
    {
        return asset(config('branding.logo'));
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
