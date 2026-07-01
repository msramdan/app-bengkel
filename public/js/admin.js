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

    function closeAllFlyouts() {
        document.querySelectorAll('.has-sub.is-flyout-open').forEach(function (item) {
            item.classList.remove('is-flyout-open');
            item.style.removeProperty('--flyout-top');
        });
    }

    function positionFlyout(item) {
        const trigger = item.querySelector('.sidebar-parent');
        const panel = item.querySelector('.collapse');

        if (!trigger || !panel) {
            return;
        }

        const triggerRect = trigger.getBoundingClientRect();
        const itemRect = item.getBoundingClientRect();
        const panelHeight = panel.offsetHeight || 120;
        const maxTop = window.innerHeight - panelHeight - 8;
        const viewportTop = Math.max(8, Math.min(triggerRect.top, maxTop));

        item.style.setProperty('--flyout-top', (viewportTop - itemRect.top) + 'px');
    }

    function openFlyout(item) {
        closeAllFlyouts();
        item.classList.add('is-flyout-open');
        positionFlyout(item);
    }

    function closeAllSubmenus() {
        closeAllFlyouts();

        document.querySelectorAll('.sidebar-nav .collapse').forEach(function (el) {
            el.classList.remove('show', 'collapsing');
            el.style.removeProperty('height');

            const instance = bootstrap.Collapse.getInstance(el);
            if (instance) {
                instance.hide();
            }
        });

        document.querySelectorAll('.sidebar-parent').forEach(function (el) {
            el.setAttribute('aria-expanded', 'false');
        });
    }

    function restoreActiveSubmenus() {
        document.querySelectorAll('.has-sub').forEach(function (item) {
            const collapse = item.querySelector('.collapse');
            const parent = item.querySelector('.sidebar-parent');
            const hasActive = item.querySelector('.sidebar-submenu-link.active');

            if (!collapse || !hasActive) {
                return;
            }

            collapse.classList.add('show');
            collapse.style.removeProperty('height');
            parent?.setAttribute('aria-expanded', 'true');
            parent?.classList.add('is-active');
        });
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
            closeAllSubmenus();
        } else {
            restoreActiveSubmenus();
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
            closeAllFlyouts();
            setSidebarCollapsed(localStorage.getItem(SIDEBAR_COLLAPSED_KEY) === '1', false);
        } else {
            layout?.classList.remove('is-sidebar-collapsed');
            closeAllFlyouts();
            restoreActiveSubmenus();
        }
    }

    sidebar?.addEventListener('mouseover', function (e) {
        const item = e.target.closest('.has-sub');
        if (!item || !isDesktop() || !isSidebarCollapsed()) {
            return;
        }
        positionFlyout(item);
    });

    sidebar?.addEventListener('click', function (e) {
        const parent = e.target.closest('.sidebar-parent');

        if (!parent || !isDesktop() || !isSidebarCollapsed()) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        const item = parent.closest('.has-sub');
        if (!item) {
            return;
        }

        if (item.classList.contains('is-flyout-open')) {
            closeAllFlyouts();
            return;
        }

        openFlyout(item);
    });

    document.addEventListener('click', function (e) {
        if (!isDesktop() || !isSidebarCollapsed()) {
            return;
        }

        if (e.target.closest('.has-sub')) {
            return;
        }

        closeAllFlyouts();
    });

    window.addEventListener('scroll', function () {
        if (!isSidebarCollapsed()) {
            return;
        }

        document.querySelectorAll('.has-sub.is-flyout-open').forEach(positionFlyout);
    }, true);

    setTheme(getTheme());
    updateCollapseToggleIcon();

    if (isDesktop() && localStorage.getItem(SIDEBAR_COLLAPSED_KEY) === '1') {
        setSidebarCollapsed(true, false);
    }

    btnTheme?.addEventListener('click', function () {
        const next = html.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
        setTheme(next);
    });

    btnOpen?.addEventListener('click', openSidebar);
    btnClose?.addEventListener('click', closeSidebar);
    backdrop?.addEventListener('click', closeSidebar);
    btnCollapse?.addEventListener('click', toggleSidebarCollapsed);

    window.addEventListener('resize', onResize);

    // Modal — tutup hanya lewat tombol Batal / silang, bukan klik area luar
    document.querySelectorAll('.modal').forEach(function (modalEl) {
        modalEl.setAttribute('data-bs-backdrop', 'static');
    });
})();
