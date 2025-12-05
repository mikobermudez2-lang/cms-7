    <footer>
        <div class="container text-center">
            <p class="mb-0">© <?= date('Y'); ?> Healthcare Center. All rights reserved.</p>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= asset('js/main.js'); ?>"></script>
    <?php if ($currentPage === 'doctors'): ?>
    <script>
        // Auto-refresh doctors list every 1 second
        (function() {
            const doctorsContainer = document.getElementById('doctorsContainer');
            if (!doctorsContainer) return;
            
            let lastDoctorIds = [];
            
            function getCurrentIds() {
                const cards = doctorsContainer.querySelectorAll('[data-doctor-id]');
                return Array.from(cards).map(card => parseInt(card.getAttribute('data-doctor-id')));
            }
            
            lastDoctorIds = getCurrentIds();
            
            async function refreshDoctors() {
                try {
                    const response = await fetch('<?= url("/api/doctors.php"); ?>');
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        const newIds = data.data.map(d => parseInt(d.id));
                        const hasChanges = JSON.stringify(newIds.sort()) !== JSON.stringify(lastDoctorIds.sort());
                        
                        if (hasChanges) {
                            window.location.reload();
                        }
                    }
                } catch (error) {
                    console.error('Error refreshing doctors:', error);
                }
            }
            
            setInterval(refreshDoctors, 1000);
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) refreshDoctors();
            });
        })();
    </script>
    <?php endif; ?>
    <?php if ($currentPage === 'appointment'): ?>
    <script>
        // Auto-refresh doctor dropdown every 1 second
        (function() {
            const doctorSelect = document.getElementById('doctor_id');
            if (!doctorSelect) return;
            
            let lastDoctorIds = [];
            
            function getCurrentIds() {
                const options = doctorSelect.querySelectorAll('option[value]');
                return Array.from(options).map(opt => parseInt(opt.value)).filter(id => id > 0);
            }
            
            lastDoctorIds = getCurrentIds();
            
            async function refreshDoctors() {
                try {
                    const response = await fetch('<?= url("/api/doctors.php"); ?>');
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        const newIds = data.data.map(d => parseInt(d.id));
                        const hasChanges = JSON.stringify(newIds.sort()) !== JSON.stringify(lastDoctorIds.sort());
                        
                        if (hasChanges) {
                            const selectedValue = doctorSelect.value;
                            let html = '<option value="">Select a doctor...</option>';
                            data.data.forEach(function(doctor) {
                                html += `<option value="${doctor.id}" ${selectedValue == doctor.id ? 'selected' : ''}>${doctor.name} - ${doctor.specialty}</option>`;
                            });
                            doctorSelect.innerHTML = html;
                            lastDoctorIds = newIds;
                        }
                    }
                } catch (error) {
                    console.error('Error refreshing doctors:', error);
                }
            }
            
            setInterval(refreshDoctors, 1000);
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) refreshDoctors();
            });
        })();
    </script>
    <?php endif; ?>
    <?php if ($currentPage === 'index'): ?>
    <script>
        // Auto-refresh announcements every 10 seconds
        (function() {
            let lastAnnouncementIds = [];
            const container = document.getElementById('announcementsContainer');
            const loadingSpinner = document.getElementById('loadingSpinner');
            const countSpan = document.getElementById('announcementCount');
            const timeSpan = document.getElementById('lastUpdateTime');
            
            // Get current announcement IDs
            function getCurrentIds() {
                const items = document.querySelectorAll('[data-announcement-id]');
                return Array.from(items).map(item => parseInt(item.getAttribute('data-announcement-id')));
            }
            
            // Initial IDs
            lastAnnouncementIds = getCurrentIds();
            
            // Fetch and update announcements
            async function refreshAnnouncements() {
                if (loadingSpinner) {
                    loadingSpinner.classList.remove('d-none');
                }
                
                try {
                    const response = await fetch('<?= url("/api/announcements.php"); ?>');
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        const newIds = data.data.map(a => a.id || 0);
                        
                        // Check if there are new announcements
                        const hasNew = newIds.some(id => !lastAnnouncementIds.includes(id));
                        
                        if (hasNew || JSON.stringify(newIds) !== JSON.stringify(lastAnnouncementIds)) {
                            // Update the announcements list
                            updateAnnouncementsList(data.data);
                            lastAnnouncementIds = newIds;
                        }
                        
                        // Update count and time
                        if (countSpan) {
                            countSpan.textContent = data.data.length;
                        }
                        if (timeSpan) {
                            const now = new Date();
                            timeSpan.textContent = now.toLocaleTimeString();
                        }
                    }
                } catch (error) {
                    console.error('Error refreshing announcements:', error);
                } finally {
                    if (loadingSpinner) {
                        loadingSpinner.classList.add('d-none');
                    }
                }
            }
            
            // Update announcements list HTML
            function updateAnnouncementsList(announcements) {
                if (!container) return;
                
                if (announcements.length === 0) {
                    container.innerHTML = '<div class="alert alert-info text-center"><p class="mb-0">No announcements at this time. Check back soon for updates!</p></div>';
                    return;
                }
                
                let html = '<div class="list-group announcement-feed" id="announcementsList">';
                announcements.forEach(function(ann) {
                    const date = new Date(ann.created_at);
                    const formattedDate = date.toLocaleDateString('en-US', { 
                        year: 'numeric', 
                        month: 'long', 
                        day: 'numeric',
                        hour: 'numeric',
                        minute: '2-digit',
                        hour12: true
                    });
                    
                    html += `
                        <div class="list-group-item mb-3 border rounded shadow-sm" data-announcement-id="${ann.id || ''}">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <small class="text-muted">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    ${formattedDate}
                                </small>
                            </div>
                            <div class="announcement-content">
                                ${ann.message}
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                html += `
                    <div class="text-center mt-3">
                        <small class="text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            Showing <span id="announcementCount">${announcements.length}</span> announcement(s) - 
                            Last updated: <span id="lastUpdateTime">${new Date().toLocaleTimeString()}</span>
                            <span class="spinner-border spinner-border-sm ms-2 d-none" id="loadingSpinner" role="status"></span>
                        </small>
                    </div>
                `;
                
                container.innerHTML = html;
                
                // Re-attach event listeners
                lastAnnouncementIds = getCurrentIds();
            }
            
            // Refresh every 1 second
            setInterval(refreshAnnouncements, 1000);
            
            // Also refresh when page becomes visible (user switches back to tab)
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    refreshAnnouncements();
                }
            });
        })();
    </script>
    <?php endif; ?>
</body>
</html>

