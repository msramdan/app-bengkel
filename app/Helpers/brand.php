<?php

if (! function_exists('brand_name')) {
    function brand_name(): string
    {
        return config('branding.name');
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
