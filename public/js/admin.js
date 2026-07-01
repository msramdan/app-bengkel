/**
 * Atha Motor — Admin layout scripts (Bootstrap 5.3, no Mazer)
 */
(function () {
    const THEME_KEY = 'atha-theme';
    const SIDEBAR_COLLAPSED_KEY = 'atha-sidebar-collapsed';
    const DESKTOP_BP = 992;

    const html = document.documentElement;
    const layout = document.getElementById('admin-layout');
    const sidebar = document.getElementById('admin-sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    const btnOpen = document.getElementById('btn-sidebar-toggle');
    const btnClose = document.getElementById('btn-sidebar-close');
    const btnCollapse = document.getElementById('btn-sidebar-collapse');
    const btnTheme = document.getElementById('btn-theme-toggle');

    function isDesktop() {
        return window.innerWidth >= DESKTOP_BP;
    }

    function getTheme() {
        return localStorage.getItem(THEME_KEY) || 'dark';
    }

    function setTheme(theme) {
        html.setAttribute('data-bs-theme', theme);
        localStorage.setItem(THEME_KEY, theme);
    }

    function isSidebarCollapsed() {
        return layout?.classList.contains('is-sidebar-collapsed') ?? false;
    }

    function setSidebarCollapsed(collapsed, persist) {
        if (!layout || !isDesktop()) {
            return;
        }

        layout.classList.toggle('is-sidebar-collapsed', collapsed);

        if (persist) {
            localStorage.setItem(SIDEBAR_COLLAPSED_KEY, collapsed ? '1' : '0');
        }

        if (collapsed) {
            document.querySelectorAll('.sidebar-nav .collapse.show').forEach(function (el) {
                const instance = bootstrap.Collapse.getInstance(el);
                if (instance) {
                    instance.hide();
                } else {
                    el.classList.remove('show');
                }
            });
        }

        updateCollapseToggleIcon();
    }

    function toggleSidebarCollapsed() {
        setSidebarCollapsed(!isSidebarCollapsed(), true);
    }

    function updateCollapseToggleIcon() {
        if (!btnCollapse) {
            return;
        }
        const icon = btnCollapse.querySelector('i');
        if (!icon) {
            return;
        }
        icon.className = isSidebarCollapsed() ? 'bi bi-layout-sidebar-inset-reverse' : 'bi bi-list';
    }

    function openSidebar() {
        sidebar?.classList.add('is-open');
        backdrop?.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar?.classList.remove('is-open');
        backdrop?.classList.remove('show');
        document.body.style.overflow = '';
    }

    function onResize() {
        if (isDesktop()) {
            closeSidebar();
            setSidebarCollapsed(localStorage.getItem(SIDEBAR_COLLAPSED_KEY) === '1', false);
        } else {
            layout?.classList.remove('is-sidebar-collapsed');
        }
    }

    // Cegah toggle collapse Bootstrap saat sidebar mini
    sidebar?.addEventListener('click', function (e) {
        const parent = e.target.closest('.sidebar-parent');
        if (parent && isDesktop() && isSidebarCollapsed()) {
            e.preventDefault();
            e.stopPropagation();
        }
    });

    setTheme(getTheme());
    updateCollapseToggleIcon();

    btnTheme?.addEventListener('click', function () {
        const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        setTheme(next);
    });

    btnOpen?.addEventListener('click', openSidebar);
    btnClose?.addEventListener('click', closeSidebar);
    backdrop?.addEventListener('click', closeSidebar);
    btnCollapse?.addEventListener('click', toggleSidebarCollapsed);

    window.addEventListener('resize', onResize);
})();
