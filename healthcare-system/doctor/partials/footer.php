    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= asset('js/main.js'); ?>"></script>
    <?php if ($activePage === 'dashboard'): ?>
    <script>
        // Auto-refresh doctor dashboard every 1 second
        (function() {
            async function refreshDashboard() {
                try {
                    const response = await fetch('<?= url("/api/doctor_dashboard.php"); ?>');
                    const data = await response.json();
                    
                    if (data.success && data.data) {
                        const counts = data.data.counts;
                        const appointments = data.data.appointments;
                        
                        // Update counts
                        const countToday = document.getElementById('countToday');
                        const countUpcoming = document.getElementById('countUpcoming');
                        const countPatients = document.getElementById('countPatients');
                        
                        if (countToday) countToday.textContent = counts.today;
                        if (countUpcoming) countUpcoming.textContent = counts.upcoming;
                        if (countPatients) countPatients.textContent = counts.patients;
                        
                        // Update appointments list
                        const appointmentsContainer = document.getElementById('appointmentsContainer');
                        if (appointmentsContainer && appointments) {
                            if (appointments.length === 0) {
                                appointmentsContainer.innerHTML = '<p class="text-muted">No upcoming appointments.</p>';
                            } else {
                                let html = '<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Date</th><th>Time</th><th>Patient</th><th>Status</th></tr></thead><tbody>';
                                appointments.forEach(function(appt) {
                                    const date = new Date(appt.date).toLocaleDateString();
                                    const time = appt.time ? new Date('2000-01-01 ' + appt.time).toLocaleTimeString('en-US', {hour: 'numeric', minute: '2-digit', hour12: true}) : 'TBD';
                                    const statusClass = appt.status === 'Waiting' ? 'warning' : (appt.status === 'Confirmed' ? 'success' : 'secondary');
                                    html += `<tr><td>${date}</td><td>${time}</td><td>${appt.patient_name}</td><td><span class="badge text-bg-${statusClass}">${appt.status}</span></td></tr>`;
                                });
                                html += '</tbody></table></div>';
                                appointmentsContainer.innerHTML = html;
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
</body>
</html>

