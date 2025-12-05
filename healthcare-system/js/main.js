const appRootAttr = document.body && document.body.dataset ? document.body.dataset.appRoot : '';
const APP_ROOT = appRootAttr ? appRootAttr : '';

function appUrl(path) {
    const sanitized = path.startsWith('/') ? path.slice(1) : path;
    if (!APP_ROOT) {
        return `/${sanitized}`;
    }

    const separator = APP_ROOT.endsWith('/') ? '' : '/';
    return `${APP_ROOT}${separator}${sanitized}`;
}

document.addEventListener('DOMContentLoaded', () => {
    const flash = document.querySelector('[data-flash]');
    if (flash) {
        setTimeout(() => flash.remove(), 5000);
    }

    const appointmentForm = document.querySelector('#appointmentForm');
    if (appointmentForm) {
        loadDoctors();
        appointmentForm.addEventListener('submit', handleAppointmentSubmit);
        
        // Add input validation for name, age, and phone
        const nameInput = document.getElementById('patientName');
        const ageInput = document.getElementById('patientAge');
        const phoneInput = document.getElementById('patientPhone');
        
        if (nameInput) {
            nameInput.addEventListener('input', function(e) {
                // Only allow letters, spaces, apostrophes, and hyphens
                this.value = this.value.replace(/[^A-Za-z\s'-]/g, '');
            });
            nameInput.addEventListener('paste', function(e) {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                const filtered = paste.replace(/[^A-Za-z\s'-]/g, '');
                this.value = filtered;
            });
        }
        
        if (ageInput) {
            ageInput.addEventListener('input', function(e) {
                // Only allow numbers
                this.value = this.value.replace(/[^0-9]/g, '');
            });
            ageInput.addEventListener('paste', function(e) {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                const filtered = paste.replace(/[^0-9]/g, '');
                this.value = filtered;
            });
        }
        
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                // Only allow numbers
                this.value = this.value.replace(/[^0-9]/g, '');
            });
            phoneInput.addEventListener('paste', function(e) {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text');
                const filtered = paste.replace(/[^0-9]/g, '');
                this.value = filtered;
            });
        }
    }

    const announcementList = document.querySelector('[data-announcements-list]');
    if (announcementList) {
        loadAnnouncements(announcementList);
    }
});

async function loadDoctors() {
    const doctorSelect = document.querySelector('#doctor_id');
    if (!doctorSelect) return;

    try {
        const response = await fetch(appUrl('api/doctors.php'));
        const data = await response.json();
        doctorSelect.innerHTML = '<option value="">Select Doctor</option>';
        data.forEach((doctor) => {
            const option = document.createElement('option');
            option.value = doctor.id;
            option.textContent = `${doctor.name} — ${doctor.specialty}`;
            doctorSelect.appendChild(option);
        });
    } catch (error) {
        console.error('Unable to load doctors', error);
    }
}

async function handleAppointmentSubmit(event) {
    event.preventDefault();
    const form = event.target;
    const button = form.querySelector('button[type="submit"]');
    
    // Get form fields
    const nameInput = document.getElementById('patientName');
    const ageInput = document.getElementById('patientAge');
    const phoneInput = document.getElementById('patientPhone');
    
    // Validate name (only letters, spaces, apostrophes, hyphens)
    if (nameInput && !/^[A-Za-z\s'-]+$/.test(nameInput.value.trim())) {
        const alertBox = document.querySelector('#appointmentAlert');
        alertBox.className = 'alert alert-danger mt-3';
        alertBox.textContent = 'Full name can only contain letters, spaces, apostrophes, and hyphens.';
        nameInput.focus();
        return;
    }
    
    // Validate age (only numbers)
    if (ageInput && !/^[0-9]+$/.test(ageInput.value.trim())) {
        const alertBox = document.querySelector('#appointmentAlert');
        alertBox.className = 'alert alert-danger mt-3';
        alertBox.textContent = 'Age must contain only numbers.';
        ageInput.focus();
        return;
    }
    
    // Validate phone (only numbers)
    if (phoneInput && !/^[0-9]+$/.test(phoneInput.value.trim())) {
        const alertBox = document.querySelector('#appointmentAlert');
        alertBox.className = 'alert alert-danger mt-3';
        alertBox.textContent = 'Phone number must contain only numbers.';
        phoneInput.focus();
        return;
    }
    
    button.disabled = true;
    button.textContent = 'Booking...';

    try {
        const formData = new FormData(form);
        const response = await fetch(appUrl('api/book_appointment.php'), {
            method: 'POST',
            body: formData,
        });

        const result = await response.json();

        const alertBox = document.querySelector('#appointmentAlert');
        if (result.success) {
            form.reset();
            alertBox.className = 'alert alert-success mt-3';
            alertBox.textContent = 'Request submitted! Our staff will reach out with your schedule.';
        } else {
            alertBox.className = 'alert alert-danger mt-3';
            alertBox.textContent = result.message ?? 'Unable to book appointment.';
        }
    } catch (error) {
        console.error(error);
        const alertBox = document.querySelector('#appointmentAlert');
        alertBox.className = 'alert alert-danger mt-3';
        alertBox.textContent = 'An error occurred. Please try again.';
    } finally {
        button.disabled = false;
        button.textContent = 'Book Appointment';
    }
}

async function loadAnnouncements(listElement) {
    listElement.innerHTML = `
        <div class="text-center">
            <div class="spinner-border text-primary" role="status" aria-hidden="true"></div>
            <span class="visually-hidden">Loading...</span>
        </div>
    `;
    try {
        const response = await fetch(appUrl('api/announcements.php'));
        const result = await response.json();

        listElement.innerHTML = '';

        if (!result.success || !Array.isArray(result.data) || !result.data.length) {
            listElement.innerHTML = '<div class="text-muted text-center">No recent announcements.</div>';
            return;
        }

        result.data.forEach((item) => {
            const entry = document.createElement('div');
            entry.className = 'list-group-item';

            const date = item.created_at
                ? new Date(item.created_at).toLocaleDateString(undefined, {
                      year: 'numeric',
                      month: 'long',
                      day: 'numeric',
                  })
                : '';

            entry.innerHTML = `
                <small class="text-muted d-block mb-2">${date}</small>
                <div class="announcement-content">${item.message}</div>
            `;
            
            // Ensure images in announcements are properly styled
            entry.querySelectorAll('img').forEach(img => {
                if (!img.style.maxWidth) {
                    img.style.maxWidth = '100%';
                    img.style.height = 'auto';
                }
            });

            listElement.appendChild(entry);
        });
    } catch (error) {
        console.error(error);
        listElement.innerHTML = '<div class="text-danger text-center">Unable to load announcements right now.</div>';
    }
}


