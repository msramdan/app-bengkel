<aside class="admin-sidebar" id="admin-sidebar">
    <div class="sidebar-inner">
        {{-- Brand header seperti panel referensi --}}
        <div class="sidebar-brand">
            <a href="{{ route('dashboard') }}" class="sidebar-brand-link">
                <span class="sidebar-brand-mark" aria-hidden="true"></span>
                <div class="sidebar-brand-info">
                    <span class="sidebar-brand-text">{{ brand_name() }}</span>
                    <span class="sidebar-brand-sub">{{ brand_tagline() }}</span>
                </div>
            </a>
            <button type="button" class="btn-sidebar-close d-lg-none" id="btn-sidebar-close" aria-label="Tutup menu">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <ul class="sidebar-menu list-unstyled mb-0">
                @foreach (config('sidebar.menus') as $group)
                    @foreach ($group['items'] as $item)
                        @if (! empty($item['submenus']))
                            @php
                                $visibleSubmenus = collect($item['submenus'])->filter(
                                    fn ($sub) => empty($sub['permission']) || auth()->user()->can($sub['permission'])
                                );
                                $submenuId = 'submenu-' . str()->slug($item['label']);
                                $isOpen = is_active_submenu($item['submenus']) === ' active';
                            @endphp
                            @if ($visibleSubmenus->isNotEmpty())
                                <li class="sidebar-menu-item has-sub">
                                    <a class="sidebar-menu-link sidebar-parent {{ $isOpen ? 'is-active' : '' }}"
                                        data-bs-toggle="collapse" href="#{{ $submenuId }}"
                                        role="button" aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                                        title="{{ $item['label'] }}">
                                        <i class="bi {{ $item['icon'] }} sidebar-menu-icon"></i>
                                        <span class="sidebar-menu-label">{{ $item['label'] }}</span>
                                        <i class="bi bi-chevron-down sidebar-chevron"></i>
                                    </a>
                                    <div class="collapse {{ $isOpen ? 'show' : '' }}" id="{{ $submenuId }}"
                                        data-bs-parent=".sidebar-menu">
                                        <div class="sidebar-flyout-head">{{ $item['label'] }}</div>
                                        <ul class="sidebar-submenu list-unstyled">
                                            @foreach ($visibleSubmenus as $submenu)
                                                <li>
                                                    <a href="{{ route($submenu['route']) }}"
                                                        class="sidebar-submenu-link{{ is_active_menu($submenu['route']) }}">
                                                        {{ $submenu['label'] }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </li>
                            @endif
                        @elseif ($item['permission'] === null || auth()->user()->can($item['permission']))
                            <li class="sidebar-menu-item">
                                <a href="{{ route($item['route']) }}"
                                    class="sidebar-menu-link{{ is_active_menu($item['route']) }}"
                                    title="{{ $item['label'] }}">
                                    <i class="bi {{ $item['icon'] }} sidebar-menu-icon"></i>
                                    <span class="sidebar-menu-label">{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endif
                    @endforeach
                @endforeach
            </ul>
        </nav>
    </div>
</aside>
