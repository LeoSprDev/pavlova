class UXIntelligence {
    constructor() {
        this.isMobile = window.innerWidth <= 768;
        this.isTablet = window.innerWidth > 768 && window.innerWidth <= 1024;
        this.isTouchDevice = 'ontouchstart' in window;
        
        this.initKeyboardShortcuts();
        this.initUserPreferences();
        this.initSmartTooltips();
        this.initMobileOptimizations();
        this.initResponsiveHandlers();
        this.initTouchOptimizations();
    }

    initKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                switch(e.key) {
                    case 's':
                        e.preventDefault();
                        this.quickSave();
                        break;
                    case 'n':
                        e.preventDefault();
                        this.newDemande();
                        break;
                }
            }
        });
    }

    initUserPreferences() {
        const preferences = localStorage.getItem('userPreferences');
        if (preferences) {
            this.applyPreferences(JSON.parse(preferences));
        }
    }

    initSmartTooltips() {
        // Placeholder for smart tooltip logic
    }

    quickSave() {
        // Placeholder for quick save action
    }

    newDemande() {
        // Placeholder for new demande action
    }

    applyPreferences(prefs) {
        // Apply stored preferences
    }

    initMobileOptimizations() {
        if (this.isMobile) {
            // Mobile-specific optimizations
            this.setupMobileSidebar();
            this.optimizeTableForMobile();
            this.setupSwipeGestures();
            this.preventZoomOnInputFocus();
        }
    }

    initResponsiveHandlers() {
        window.addEventListener('resize', () => {
            this.isMobile = window.innerWidth <= 768;
            this.isTablet = window.innerWidth > 768 && window.innerWidth <= 1024;
            this.handleResize();
        });

        window.addEventListener('orientationchange', () => {
            setTimeout(() => this.handleOrientationChange(), 500);
        });
    }

    initTouchOptimizations() {
        if (this.isTouchDevice) {
            // Add touch-friendly classes
            document.body.classList.add('touch-device');
            
            // Improve touch targets
            this.enhanceTouchTargets();
            
            // Optimize scroll behavior
            this.optimizeScrolling();
        }
    }

    setupMobileSidebar() {
        const sidebar = document.querySelector('.fi-sidebar');
        const overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        
        if (sidebar) {
            // Create mobile toggle button
            const toggle = document.createElement('button');
            toggle.className = 'mobile-sidebar-toggle';
            toggle.innerHTML = '☰';
            toggle.addEventListener('click', () => this.toggleMobileSidebar());
            
            // Add to topbar
            const topbar = document.querySelector('.fi-topbar');
            if (topbar) {
                topbar.prepend(toggle);
            }
            
            // Add overlay
            document.body.appendChild(overlay);
            overlay.addEventListener('click', () => this.closeMobileSidebar());
        }
    }

    toggleMobileSidebar() {
        const sidebar = document.querySelector('.fi-sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        
        if (sidebar && overlay) {
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
            document.body.classList.toggle('sidebar-open');
        }
    }

    closeMobileSidebar() {
        const sidebar = document.querySelector('.fi-sidebar');
        const overlay = document.querySelector('.sidebar-overlay');
        
        if (sidebar && overlay) {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
            document.body.classList.remove('sidebar-open');
        }
    }

    optimizeTableForMobile() {
        const tables = document.querySelectorAll('.fi-ta-table');
        
        tables.forEach(table => {
            if (this.isMobile) {
                // Make table horizontally scrollable
                table.style.overflowX = 'auto';
                table.style.webkitOverflowScrolling = 'touch';
                
                // Add scroll indicators
                this.addScrollIndicators(table);
            }
        });
    }

    addScrollIndicators(table) {
        const wrapper = table.closest('.fi-ta');
        if (wrapper) {
            const leftIndicator = document.createElement('div');
            const rightIndicator = document.createElement('div');
            
            leftIndicator.className = 'scroll-indicator left';
            rightIndicator.className = 'scroll-indicator right';
            
            wrapper.appendChild(leftIndicator);
            wrapper.appendChild(rightIndicator);
            
            table.addEventListener('scroll', () => {
                const scrollLeft = table.scrollLeft;
                const maxScroll = table.scrollWidth - table.clientWidth;
                
                leftIndicator.style.opacity = scrollLeft > 0 ? '1' : '0';
                rightIndicator.style.opacity = scrollLeft < maxScroll ? '1' : '0';
            });
        }
    }

    setupSwipeGestures() {
        let startX = 0;
        let startY = 0;
        
        document.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        });
        
        document.addEventListener('touchend', (e) => {
            const endX = e.changedTouches[0].clientX;
            const endY = e.changedTouches[0].clientY;
            
            const deltaX = endX - startX;
            const deltaY = endY - startY;
            
            // Horizontal swipe (sidebar)
            if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > 50) {
                if (deltaX > 0 && startX < 50) {
                    // Swipe right from left edge - open sidebar
                    this.toggleMobileSidebar();
                } else if (deltaX < 0 && startX > window.innerWidth - 50) {
                    // Swipe left from right edge - close sidebar
                    this.closeMobileSidebar();
                }
            }
        });
    }

    preventZoomOnInputFocus() {
        const inputs = document.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.style.fontSize !== '16px') {
                input.style.fontSize = '16px';
            }
        });
    }

    enhanceTouchTargets() {
        const touchTargets = document.querySelectorAll('button, .fi-link, .fi-ac-btn');
        
        touchTargets.forEach(target => {
            const rect = target.getBoundingClientRect();
            if (rect.height < 44 || rect.width < 44) {
                target.style.minHeight = '44px';
                target.style.minWidth = '44px';
                target.style.display = 'flex';
                target.style.alignItems = 'center';
                target.style.justifyContent = 'center';
            }
        });
    }

    optimizeScrolling() {
        // Smooth scrolling for touch devices
        document.documentElement.style.scrollBehavior = 'smooth';
        
        // Optimize scroll performance
        let ticking = false;
        
        document.addEventListener('scroll', () => {
            if (!ticking) {
                requestAnimationFrame(() => {
                    this.handleScroll();
                    ticking = false;
                });
                ticking = true;
            }
        });
    }

    handleScroll() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        // Show/hide elements based on scroll
        const header = document.querySelector('.fi-header');
        if (header && this.isMobile) {
            if (scrollTop > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        }
    }

    handleResize() {
        // Recalculate optimizations on resize
        this.optimizeTableForMobile();
        
        if (!this.isMobile) {
            this.closeMobileSidebar();
        }
    }

    handleOrientationChange() {
        // Refresh layout after orientation change
        this.handleResize();
        
        // Force repaint to fix iOS issues
        document.body.style.display = 'none';
        document.body.offsetHeight; // trigger reflow
        document.body.style.display = '';
    }

    // Form optimizations for mobile
    optimizeFormsForMobile() {
        if (this.isMobile) {
            const forms = document.querySelectorAll('form');
            
            forms.forEach(form => {
                // Add mobile-optimized classes
                form.classList.add('mobile-optimized');
                
                // Optimize input spacing
                const inputs = form.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.style.marginBottom = '1rem';
                });
            });
        }
    }

    // Widget responsiveness
    optimizeWidgets() {
        const widgets = document.querySelectorAll('.fi-widget');
        
        widgets.forEach(widget => {
            if (this.isMobile) {
                widget.style.marginBottom = '1rem';
                widget.style.gridColumn = '1 / -1';
            }
        });
    }
}

// Add CSS for mobile enhancements
const mobileStyles = `
<style>
.mobile-sidebar-toggle {
    display: none;
    background: none;
    border: none;
    font-size: 1.5rem;
    padding: 0.5rem;
    cursor: pointer;
}

@media (max-width: 768px) {
    .mobile-sidebar-toggle {
        display: block !important;
    }
    
    .fi-sidebar.mobile-open {
        transform: translateX(0) !important;
    }
    
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 998;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s ease;
    }
    
    .sidebar-overlay.active {
        opacity: 1;
        visibility: visible;
    }
    
    .scroll-indicator {
        position: absolute;
        top: 0;
        bottom: 0;
        width: 20px;
        background: linear-gradient(to right, rgba(0,0,0,0.1), transparent);
        z-index: 10;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .scroll-indicator.left {
        left: 0;
    }
    
    .scroll-indicator.right {
        right: 0;
        background: linear-gradient(to left, rgba(0,0,0,0.1), transparent);
    }
    
    .fi-header.scrolled {
        transform: translateY(-50%);
        transition: transform 0.3s ease;
    }
}

.touch-device button,
.touch-device .fi-link,
.touch-device .fi-ac-btn {
    cursor: pointer;
    -webkit-tap-highlight-color: rgba(0,0,0,0.1);
}
</style>
`;

document.head.insertAdjacentHTML('beforeend', mobileStyles);

new UXIntelligence();
