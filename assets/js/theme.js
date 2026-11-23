// Theme switcher: Auto, Light, Dark (The Only AI Freedom Persistent Theme Engine)
(function () {
    const root = document.documentElement;

    function getPreferredTheme() {
        try {
            return localStorage.getItem('toaf-theme') || 'auto';
        } catch (e) {
            return 'auto';
        }
    }

    function updateButtonUI(theme) {
        document.querySelectorAll('[data-theme-btn]').forEach(btn => {
            const btnTheme = btn.getAttribute('data-theme-btn');
            if (btnTheme === theme) {
                btn.classList.add('bg-[#d9b46c]/25', 'text-[#d9b46c]', 'border', 'border-[#d9b46c]/50', 'shadow-sm', 'font-bold');
                btn.classList.remove('text-[#a3a6b0]', 'border-transparent');
            } else {
                btn.classList.remove('bg-[#d9b46c]/25', 'text-[#d9b46c]', 'border', 'border-[#d9b46c]/50', 'shadow-sm', 'font-bold');
                btn.classList.add('text-[#a3a6b0]', 'border-transparent');
            }
        });
    }

    function applyTheme(theme) {
        const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
        const activeMode = theme === 'auto' ? (prefersDark ? 'dark' : 'light') : theme;

        root.setAttribute('data-theme', theme);
        root.setAttribute('data-mode', activeMode);

        if (activeMode === 'dark') {
            root.classList.add('dark');
        } else {
            root.classList.remove('dark');
        }

        updateButtonUI(theme);
    }

    // Immediately apply on script load (in <head>) to prevent flash
    const initialTheme = getPreferredTheme();
    applyTheme(initialTheme);

    // Re-sync UI buttons once DOM is fully ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            updateButtonUI(getPreferredTheme());
        });
    } else {
        updateButtonUI(initialTheme);
    }

    // Watch system color scheme changes if user chose auto mode
    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (getPreferredTheme() === 'auto') {
                applyTheme('auto');
            }
        });
    }

    // Global switcher accessible from onclick
    window.setAppTheme = function (theme) {
        try {
            if (theme === 'auto') {
                localStorage.removeItem('toaf-theme');
            } else {
                localStorage.setItem('toaf-theme', theme);
            }
        } catch (e) {}
        applyTheme(theme);
    };
})();
