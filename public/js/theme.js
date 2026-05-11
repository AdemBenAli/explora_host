(function () {
    const STORAGE_KEY = 'explora-theme';

    function resolveTheme(mode) {
        if (mode === 'dark') return 'dark';
        if (mode === 'light') return 'light';
        const h = new Date().getHours();
        return (h >= 7 && h < 19) ? 'light' : 'dark';
    }

    function applyTheme() {
        const mode = localStorage.getItem(STORAGE_KEY) || 'auto';
        document.documentElement.setAttribute('data-theme', resolveTheme(mode));
    }

    // Apply immediately to avoid flash
    applyTheme();

    // Re-apply on storage changes (cross-tab sync)
    window.addEventListener('storage', function (e) {
        if (e.key === STORAGE_KEY) applyTheme();
    });

    // Expose globals used by the theme toggle buttons in templates
    window.pickTheme = function (mode) {
        localStorage.setItem(STORAGE_KEY, mode);
        document.documentElement.setAttribute('data-theme', resolveTheme(mode));
        syncThemeNav();
        const menu = document.getElementById('theme-nav-menu');
        if (menu) menu.style.display = 'none';
    };

    window.toggleThemeMenu = function (e) {
        e.preventDefault();
        const menu = document.getElementById('theme-nav-menu');
        if (menu) menu.style.display = menu.style.display === 'flex' ? 'none' : 'flex';
    };

    window.syncThemeNav = function () {
        const ICONS = { auto: '🌗', light: '☀️', dark: '🌙' };
        const mode = localStorage.getItem(STORAGE_KEY) || 'auto';
        const icon = document.getElementById('theme-nav-icon');
        if (icon) icon.textContent = ICONS[mode] || '';
        document.querySelectorAll('.theme-nav-option').forEach(function (el) {
            el.classList.toggle('active', el.dataset.mode === mode);
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        if (window.syncThemeNav) window.syncThemeNav();
    });
})();
