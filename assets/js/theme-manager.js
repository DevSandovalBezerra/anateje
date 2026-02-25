(function() {
    'use strict';
    
    const ThemeManager = {
        themes: ['light', 'dark-blue', 'monokai'],
        currentTheme: 'light',
        storageKey: 'lidergest_theme',
        
        init: function() {
            this.loadTheme();
            this.setupEventListeners();
            this.applyTheme(this.currentTheme, false);
        },
        
        loadTheme: function() {
            try {
                const saved = localStorage.getItem(this.storageKey);
                if (saved && this.themes.includes(saved)) {
                    this.currentTheme = saved;
                } else {
                    this.currentTheme = 'light';
                }
            } catch (e) {
                console.error('Erro ao carregar tema:', e);
                this.currentTheme = 'light';
            }
        },
        
        applyTheme: function(theme, animate = true) {
            if (!this.themes.includes(theme)) {
                console.warn('Tema inválido:', theme);
                return;
            }
            
            const html = document.documentElement;
            const body = document.body;
            
            if (!animate) {
                html.style.transition = 'none';
                body.style.transition = 'none';
            }
            
            html.setAttribute('data-theme', theme);
            this.currentTheme = theme;
            
            try {
                localStorage.setItem(this.storageKey, theme);
            } catch (e) {
                console.error('Erro ao salvar tema:', e);
            }
            
            if (!animate) {
                setTimeout(() => {
                    html.style.transition = '';
                    body.style.transition = '';
                }, 0);
            }
            
            this.updateThemeToggle(theme);
            this.updateChartColors(theme);
            this.dispatchThemeChange(theme);
        },
        
        updateThemeToggle: function(theme) {
            const toggle = document.getElementById('theme-toggle');
            if (!toggle) return;
            
            const icon = toggle.querySelector('i[data-lucide]');
            const text = toggle.querySelector('.theme-text');
            
            if (icon) {
                const icons = {
                    'light': 'sun',
                    'dark-blue': 'moon',
                    'monokai': 'palette'
                };
                icon.setAttribute('data-lucide', icons[theme] || 'sun');
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }
            
            if (text) {
                const names = {
                    'light': 'Claro',
                    'dark-blue': 'Dark Blue',
                    'monokai': 'Monokai'
                };
                text.textContent = names[theme] || 'Claro';
            }
        },
        
        getThemeColors: function(theme) {
            const colorSets = {
                'light': {
                    primary: '#8B5CF6',
                    primaryRgba: 'rgba(139, 92, 246, 0.8)',
                    primaryLight: 'rgba(139, 92, 246, 0.1)',
                    secondary: '#7B3ED6',
                    success: '#10b981',
                    warning: '#f59e0b',
                    error: '#ef4444',
                    blue: '#3B82F6',
                    blueRgba: 'rgba(59, 130, 246, 0.1)'
                },
                'dark-blue': {
                    primary: '#60a5fa',
                    primaryRgba: 'rgba(96, 165, 250, 0.8)',
                    primaryLight: 'rgba(96, 165, 250, 0.1)',
                    secondary: '#3b82f6',
                    success: '#34d399',
                    warning: '#fbbf24',
                    error: '#f87171',
                    blue: '#60a5fa',
                    blueRgba: 'rgba(96, 165, 250, 0.1)'
                },
                'monokai': {
                    primary: '#66d9ef',
                    primaryRgba: 'rgba(102, 217, 239, 0.8)',
                    primaryLight: 'rgba(102, 217, 239, 0.1)',
                    secondary: '#ae81ff',
                    success: '#a6e22e',
                    warning: '#e6db74',
                    error: '#f92672',
                    blue: '#66d9ef',
                    blueRgba: 'rgba(102, 217, 239, 0.1)'
                }
            };
            return colorSets[theme] || colorSets.light;
        },
        
        updateChartColors: function(theme) {
            if (typeof Chart === 'undefined') return;
            
            const colors = this.getThemeColors(theme);
            const textColor = getComputedStyle(document.documentElement).getPropertyValue('--text-primary').trim();
            const borderColor = getComputedStyle(document.documentElement).getPropertyValue('--border-primary').trim();
            const bgColor = getComputedStyle(document.documentElement).getPropertyValue('--bg-card').trim();
            
            Chart.defaults.color = textColor;
            Chart.defaults.borderColor = borderColor;
            Chart.defaults.backgroundColor = bgColor;
            
            if (window.chartInstances) {
                window.chartInstances.forEach(chart => {
                    if (chart && typeof chart.update === 'function') {
                        chart.data.datasets.forEach((dataset, index) => {
                            if (dataset.backgroundColor) {
                                if (Array.isArray(dataset.backgroundColor)) {
                                    dataset.backgroundColor = [
                                        colors.primaryRgba,
                                        colors.secondary,
                                        colors.success,
                                        colors.warning,
                                        colors.error
                                    ];
                                } else if (dataset.backgroundColor.includes('rgba(139, 92, 246')) {
                                    dataset.backgroundColor = colors.primaryRgba;
                                } else if (dataset.backgroundColor.includes('rgba(59, 130, 246')) {
                                    dataset.backgroundColor = colors.blueRgba;
                                }
                            }
                            if (dataset.borderColor) {
                                if (Array.isArray(dataset.borderColor)) {
                                    dataset.borderColor = [
                                        colors.primary,
                                        colors.secondary,
                                        colors.success,
                                        colors.warning,
                                        colors.error
                                    ];
                                } else if (dataset.borderColor === '#8B5CF6' || dataset.borderColor === '#3B82F6') {
                                    dataset.borderColor = colors.primary;
                                }
                            }
                            if (dataset.pointBackgroundColor && (dataset.pointBackgroundColor === '#3B82F6' || dataset.pointBackgroundColor === '#8B5CF6')) {
                                dataset.pointBackgroundColor = colors.primary;
                            }
                        });
                        chart.options.scales = chart.options.scales || {};
                        if (chart.options.scales.y) {
                            chart.options.scales.y.grid.color = borderColor + '40';
                            chart.options.scales.y.ticks.color = textColor;
                        }
                        if (chart.options.scales.x) {
                            chart.options.scales.x.grid.color = borderColor + '40';
                            chart.options.scales.x.ticks.color = textColor;
                        }
                        if (chart.options.plugins && chart.options.plugins.tooltip) {
                            chart.options.plugins.tooltip.backgroundColor = bgColor;
                            chart.options.plugins.tooltip.titleColor = textColor;
                            chart.options.plugins.tooltip.bodyColor = textColor;
                        }
                        chart.update('none');
                    }
                });
            }
        },
        
        registerChart: function(chart) {
            if (!window.chartInstances) {
                window.chartInstances = [];
            }
            if (chart && !window.chartInstances.includes(chart)) {
                window.chartInstances.push(chart);
            }
        },
        
        dispatchThemeChange: function(theme) {
            const event = new CustomEvent('themechange', {
                detail: { theme: theme }
            });
            window.dispatchEvent(event);
        },
        
        setupEventListeners: function() {
            window.addEventListener('storage', (e) => {
                if (e.key === this.storageKey && e.newValue !== this.currentTheme) {
                    this.applyTheme(e.newValue);
                }
            });
            
            const toggle = document.getElementById('theme-toggle');
            if (toggle) {
                toggle.addEventListener('click', (e) => {
                    e.stopPropagation();
                    this.showThemeMenu(toggle);
                });
            }
            
            document.addEventListener('click', (e) => {
                const menu = document.getElementById('theme-menu');
                if (menu && !menu.contains(e.target) && !toggle.contains(e.target)) {
                    menu.classList.add('hidden');
                }
            });
        },
        
        showThemeMenu: function(button) {
            let menu = document.getElementById('theme-menu');
            
            if (!menu) {
                menu = this.createThemeMenu();
                document.body.appendChild(menu);
            }
            
            const rect = button.getBoundingClientRect();
            const menuWidth = 200;
            const menuHeight = 150;
            let top = rect.bottom + 5;
            let right = window.innerWidth - rect.right;
            
            if (top + menuHeight > window.innerHeight) {
                top = rect.top - menuHeight - 5;
            }
            
            if (right + menuWidth > window.innerWidth) {
                right = window.innerWidth - rect.left - 5;
            }
            
            menu.style.top = top + 'px';
            menu.style.right = right + 'px';
            
            menu.classList.toggle('hidden');
            
            if (!menu.classList.contains('hidden')) {
                this.updateMenuSelection(menu);
            }
        },
        
        createThemeMenu: function() {
            const menu = document.createElement('div');
            menu.id = 'theme-menu';
            menu.className = 'hidden fixed z-50 rounded-lg shadow-xl py-2 min-w-48';
            menu.setAttribute('style', 'background: var(--bg-card); border: 1px solid var(--border-primary); box-shadow: var(--shadow-lg);');
            
            const themes = [
                { id: 'light', name: 'Claro', icon: 'sun', desc: 'Tema claro padrão' },
                { id: 'dark-blue', name: 'Dark Blue', icon: 'moon', desc: 'Tema escuro azul' },
                { id: 'monokai', name: 'Monokai', icon: 'palette', desc: 'Tema Monokai colorido' }
            ];
            
            themes.forEach((theme, index) => {
                const item = document.createElement('button');
                item.className = 'w-full flex items-center px-4 py-2 text-left transition-colors';
                item.setAttribute('style', 'color: var(--text-primary);');
                item.setAttribute('onmouseover', 'this.style.backgroundColor = "var(--bg-hover)"');
                item.setAttribute('onmouseout', 'this.style.backgroundColor = "transparent"');
                item.setAttribute('data-theme', theme.id);
                if (index < themes.length - 1) {
                    item.setAttribute('style', item.getAttribute('style') + ' border-bottom: 1px solid var(--border-primary);');
                }
                
                item.innerHTML = `
                    <i data-lucide="${theme.icon}" class="w-4 h-4 mr-3" style="color: var(--text-secondary);"></i>
                    <div class="flex-1">
                        <div class="font-medium" style="color: var(--text-primary);">${theme.name}</div>
                        <div class="text-xs" style="color: var(--text-light);">${theme.desc}</div>
                    </div>
                    <i data-lucide="check" class="w-4 h-4 ml-2 theme-check hidden" style="color: var(--primary-purple);"></i>
                `;
                
                item.addEventListener('click', () => {
                    this.applyTheme(theme.id);
                    menu.classList.add('hidden');
                });
                
                menu.appendChild(item);
            });
            
            if (typeof lucide !== 'undefined') {
                setTimeout(() => lucide.createIcons(), 100);
            }
            
            return menu;
        },
        
        updateMenuSelection: function(menu) {
            const checks = menu.querySelectorAll('.theme-check');
            const items = menu.querySelectorAll('[data-theme]');
            
            items.forEach((item, index) => {
                const theme = item.getAttribute('data-theme');
                if (theme === this.currentTheme) {
                    checks[index].classList.remove('hidden');
                } else {
                    checks[index].classList.add('hidden');
                }
            });
        },
        
        getCurrentTheme: function() {
            return this.currentTheme;
        }
    };
    
    window.registerChart = function(chart) {
        ThemeManager.registerChart(chart);
    };
    
    window.getThemeColors = function() {
        return ThemeManager.getThemeColors(ThemeManager.getCurrentTheme());
    };
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            ThemeManager.init();
        });
    } else {
        ThemeManager.init();
    }
    
    window.ThemeManager = ThemeManager;
})();

