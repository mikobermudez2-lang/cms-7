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
            const toggleBtn = document.getElementById('sidebarToggle');
            const mobileToggleBtn = document.getElementById('mobileMenuToggle');
            
            // IMMEDIATELY check and apply collapsed state on page load (before any rendering)
            const savedState = localStorage.getItem('adminSidebarCollapsed');
            let isCollapsed = savedState === 'true';
            
            // Force collapsed state immediately if it should be collapsed
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
                // Also set cookie for server-side rendering
                document.cookie = 'adminSidebarCollapsed=true; path=/; max-age=31536000';
            } else {
                sidebar.classList.remove('collapsed');
                document.cookie = 'adminSidebarCollapsed=false; path=/; max-age=31536000';
            }
            
            // Prevent any expansion during page load
            sidebar.style.transition = 'none';
            setTimeout(function() {
                sidebar.style.transition = '';
            }, 100);
            
            // Function to enforce collapsed state
            function enforceCollapsedState() {
                const shouldBeCollapsed = localStorage.getItem('adminSidebarCollapsed') === 'true';
                if (shouldBeCollapsed && !sidebar.classList.contains('collapsed')) {
                    sidebar.classList.add('collapsed');
                }
            }
            
            // Desktop toggle - only toggle when clicking the toggle button itself
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    isCollapsed = !isCollapsed;
                    if (isCollapsed) {
                        sidebar.classList.add('collapsed');
                        document.cookie = 'adminSidebarCollapsed=true; path=/; max-age=31536000';
                    } else {
                        sidebar.classList.remove('collapsed');
                        document.cookie = 'adminSidebarCollapsed=false; path=/; max-age=31536000';
                    }
                    localStorage.setItem('adminSidebarCollapsed', isCollapsed);
                    updateToggleIcon();
                });
            }
            
            // Prevent sidebar from expanding when clicking anything except toggle button
            sidebar.addEventListener('click', function(e) {
                // If collapsed and not clicking toggle button, prevent expansion
                const clickedToggle = e.target.closest('#sidebarToggle') || e.target.closest('.sidebar-toggle-btn');
                if (!clickedToggle && isCollapsed) {
                    // Force keep collapsed - prevent any expansion
                    e.stopPropagation();
                    if (!sidebar.classList.contains('collapsed')) {
                        sidebar.classList.add('collapsed');
                    }
                    // Re-enforce after a brief moment
                    setTimeout(enforceCollapsedState, 50);
                }
            }, true);
            
            // Aggressively monitor and enforce collapsed state
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        // Check if sidebar should be collapsed
                        const shouldBeCollapsed = isCollapsed || localStorage.getItem('adminSidebarCollapsed') === 'true';
                        if (shouldBeCollapsed && !sidebar.classList.contains('collapsed')) {
                            // Immediately force it back to collapsed
                            sidebar.classList.add('collapsed');
                        }
                    }
                });
            });
            
            observer.observe(sidebar, {
                attributes: true,
                attributeFilter: ['class']
            });
            
            // Prevent nav links from expanding sidebar - set cookie before navigation
            const navLinks = sidebar.querySelectorAll('.nav-link');
            navLinks.forEach(function(link) {
                link.addEventListener('click', function(e) {
                    if (isCollapsed) {
                        // Save state before navigation
                        localStorage.setItem('adminSidebarCollapsed', 'true');
                        document.cookie = 'adminSidebarCollapsed=true; path=/; max-age=31536000';
                    }
                });
            });
            
            // Continuously enforce collapsed state if it should be collapsed
            setInterval(function() {
                if (isCollapsed || localStorage.getItem('adminSidebarCollapsed') === 'true') {
                    if (!sidebar.classList.contains('collapsed')) {
                        sidebar.classList.add('collapsed');
                    }
                }
            }, 50);
            
            // Mobile toggle
            const backdrop = document.getElementById('sidebarBackdrop');
            if (mobileToggleBtn) {
                mobileToggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    if (backdrop) {
                        backdrop.classList.toggle('show');
                    }
                });
            }
            
            // Close sidebar when clicking backdrop
            if (backdrop) {
                backdrop.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    backdrop.classList.remove('show');
                });
            }
            
            // Update toggle icon (hamburger stays the same, just rotates)
            function updateToggleIcon() {
                // Icon rotation is handled by CSS, no need to change class
            }
            
            // Close mobile sidebar when clicking outside
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    if (!sidebar.contains(e.target) && !mobileToggleBtn.contains(e.target)) {
                        sidebar.classList.remove('show');
                        if (backdrop) {
                            backdrop.classList.remove('show');
                        }
                    }
                }
            });
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('show');
                    if (backdrop) {
                        backdrop.classList.remove('show');
                    }
                }
            });
        })();
    </script>
    <?php if ($activePage === 'dashboard'): ?>
    <script>
        // Auto-refresh dashboard stats every 1 second
        (function() {
            async function refreshDashboard() {
                try {
                    const response = await fetch('<?= url("/api/dashboard_stats.php"); ?>');
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        const stats = data.data.totals;
                        const appointments = data.data.appointments_today;
                        const announcements = data.data.latest_announcements;
                        
                        // Update statistics
                        const statPatients = document.getElementById('statPatients');
                        const statDoctors = document.getElementById('statDoctors');
                        const statAppointments = document.getElementById('statAppointments');
                        const statAnnouncements = document.getElementById('statAnnouncements');
                        
                        if (statPatients) statPatients.textContent = stats.patients;
                        if (statDoctors) statDoctors.textContent = stats.doctors;
                        if (statAppointments) statAppointments.textContent = stats.appointments_today;
                        if (statAnnouncements) statAnnouncements.textContent = stats.announcements;
                        
                        // Update appointments table
                        const container = document.getElementById('appointmentsTodayContainer');
                        const tbody = document.getElementById('appointmentsTodayBody');
                        
                        if (container && appointments) {
                            if (appointments.length === 0) {
                                container.innerHTML = '<p class="text-muted">No appointments scheduled for today.</p>';
                            } else {
                                let html = '<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Time</th><th>Patient</th><th>Doctor</th><th>Status</th></tr></thead><tbody id="appointmentsTodayBody">';
                                appointments.forEach(function(appt) {
                                    const timeLabel = appt.time ? new Date('2000-01-01 ' + appt.time).toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true}) : 'To be scheduled';
                                    const statusClass = appt.status === 'Waiting' ? 'warning' : (appt.status === 'Confirmed' ? 'success' : 'secondary');
                                    html += `<tr><td>${timeLabel}</td><td>${appt.patient_name}</td><td>${appt.doctor_name}</td><td><span class="badge text-bg-${statusClass}">${appt.status}</span></td></tr>`;
                                });
                                html += '</tbody></table></div>';
                                container.innerHTML = html;
                            }
                        }
                    }
                } catch (error) {
                    console.error('Error refreshing dashboard:', error);
                }
            }
            
            setInterval(refreshDashboard, 1000);
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) refreshDashboard();
            });
        })();
    </script>
    <?php endif; ?>
    <?php if ($activePage === 'appointments'): ?>
    <script>
        // Auto-refresh appointments list every 1 second
        (function() {
            const appointmentsTable = document.querySelector('.appointments-table tbody');
            if (!appointmentsTable) return;
            
            let lastAppointmentIds = [];
            
            function getCurrentIds() {
                const rows = appointmentsTable.querySelectorAll('tr[data-appointment-id]');
                return Array.from(rows).map(row => parseInt(row.getAttribute('data-appointment-id')));
            }
            
            lastAppointmentIds = getCurrentIds();
            
            async function refreshAppointments() {
                try {
                    const url = new URL('<?= url("/api/appointments_list.php"); ?>', window.location.origin);
                    const statusFilter = new URLSearchParams(window.location.search).get('status');
                    const dateFilter = new URLSearchParams(window.location.search).get('date');
                    if (statusFilter) url.searchParams.set('status', statusFilter);
                    if (dateFilter) url.searchParams.set('date', dateFilter);
                    
                    const response = await fetch(url);
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        const newIds = data.data.map(a => parseInt(a.id));
                        const hasChanges = JSON.stringify(newIds.sort()) !== JSON.stringify(lastAppointmentIds.sort());
                        
                        if (hasChanges) {
                            // Reload page to show updated list (simpler than rebuilding complex table)
                            window.location.reload();
                        }
                    }
                } catch (error) {
                    console.error('Error refreshing appointments:', error);
                }
            }
            
            setInterval(refreshAppointments, 1000);
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) refreshAppointments();
            });
        })();
    </script>
    <?php endif; ?>
</body>
</html>

