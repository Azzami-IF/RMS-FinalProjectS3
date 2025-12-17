// Custom scripts

// Theme management
const ThemeManager = {
    applyTheme: function(theme) {
        console.log('Applying theme:', theme);
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
        console.log('Current data-theme attribute:', html.getAttribute('data-theme'));
    },

    loadTheme: function() {
        let savedTheme = localStorage.getItem('theme') || 'light';
        console.log('localStorage theme:', savedTheme);
        
        // If we have user preferences from server, prioritize those
        if (window.userPreferences && window.userPreferences.theme) {
            savedTheme = window.userPreferences.theme;
            localStorage.setItem('theme', savedTheme); // Sync localStorage
            console.log('Server theme:', savedTheme);
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

// Initialize theme on page load
document.addEventListener('DOMContentLoaded', function() {
    ThemeManager.init();
});

// Make ThemeManager globally available
window.ThemeManager = ThemeManager;