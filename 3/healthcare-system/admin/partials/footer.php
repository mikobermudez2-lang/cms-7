                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= asset('js/main.js'); ?>"></script>
    <script>
        // Sidebar Toggle Functionality
        (function() {
            const sidebar = document.getElementById('adminSidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const mobileMenuToggle = document.getElementById('mobileMenuToggle');
            const sidebarBackdrop = document.getElementById('sidebarBackdrop');
            
            // Desktop sidebar toggle
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent anchor navigation
                    const isCollapsed = sidebar.classList.toggle('collapsed');
                    this.setAttribute('aria-expanded', !isCollapsed);
                    
                    // Save state in cookie and localStorage
                    document.cookie = `adminSidebarCollapsed=${isCollapsed}; path=/; max-age=${60*60*24*365}; SameSite=Lax`;
                    localStorage.setItem('adminSidebarCollapsed', isCollapsed);
                });
            }
            
            // Mobile menu toggle
            if (mobileMenuToggle) {
                mobileMenuToggle.addEventListener('click', function() {
                    const isShown = sidebar.classList.toggle('show');
                    sidebarBackdrop.classList.toggle('show', isShown);
                    this.setAttribute('aria-expanded', isShown);
                    
                    // Prevent body scroll when mobile menu is open
                    document.body.style.overflow = isShown ? 'hidden' : '';
                });
            }
            
            // Close mobile menu when clicking backdrop
            if (sidebarBackdrop) {
                sidebarBackdrop.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    this.classList.remove('show');
                    if (mobileMenuToggle) {
                        mobileMenuToggle.setAttribute('aria-expanded', 'false');
                    }
                    document.body.style.overflow = '';
                });
            }
            
            // Close mobile menu on window resize to desktop
            let resizeTimer;
            window.addEventListener('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    if (window.innerWidth > 768) {
                        sidebar.classList.remove('show');
                        sidebarBackdrop.classList.remove('show');
                        document.body.style.overflow = '';
                        if (mobileMenuToggle) {
                            mobileMenuToggle.setAttribute('aria-expanded', 'false');
                        }
                    }
                }, 250);
            });
            
            // Keyboard accessibility - Escape to close mobile menu
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && sidebar.classList.contains('show')) {
                    sidebar.classList.remove('show');
                    sidebarBackdrop.classList.remove('show');
                    document.body.style.overflow = '';
                    if (mobileMenuToggle) {
                        mobileMenuToggle.setAttribute('aria-expanded', 'false');
                        mobileMenuToggle.focus();
                    }
                }
            });
        })();
    </script>
</body>
</html>

