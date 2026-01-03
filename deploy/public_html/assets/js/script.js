const ThemeManager = {
    applyTheme: function(theme) {
        const html = document.documentElement;

        if (theme === 'dark') {
            html.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
        } else if (theme === 'light') {
            html.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
        } else if (theme === 'auto') {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (prefersDark) {
                html.setAttribute('data-theme', 'dark');
            } else {
                html.removeAttribute('data-theme');
            }
            localStorage.setItem('theme', 'auto');
        }
    },

    loadTheme: function() {
        let savedTheme = localStorage.getItem('theme') || 'light';
        
        if (window.userPreferences && window.userPreferences.theme) {
            savedTheme = window.userPreferences.theme;
            localStorage.setItem('theme', savedTheme); // Sync localStorage
        }
        
        this.applyTheme(savedTheme);
    },

    init: function() {
        this.loadTheme(); // Load theme on initialization
        
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'auto') {
                this.applyTheme('auto');
            }
        });
    }
};

document.addEventListener('DOMContentLoaded', function() {
    ThemeManager.init();
});

window.ThemeManager = ThemeManager;