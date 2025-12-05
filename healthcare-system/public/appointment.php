<?php
require_once __DIR__ . '/../includes/init.php';

$pageTitle = 'Book Appointment';
$currentPage = 'appointment';

// Load doctors from database
$db = get_db();
$doctorsStmt = $db->query('SELECT id, name, specialty FROM doctors ORDER BY name');
$doctors = $doctorsStmt->fetchAll();

// Get selected doctor from URL
$selectedDoctorId = isset($_GET['doctor_id']) ? (int) $_GET['doctor_id'] : 0;

include __DIR__ . '/includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5">
                <h2 class="fw-bold mb-3">Schedule an appointment</h2>
                <p class="text-muted">Fill out the form and our admin team will confirm your slot. You will receive status updates via phone or email once a doctor reviews the request.</p>
                <ul class="list-unstyled">
                    <li>• Status starts as Waiting</li>
                    <li>• Our coordination team reviews every request in real-time</li>
                    <li>• A staff member will contact you with the exact schedule</li>
                    <li>• Bring a valid ID on your visit</li>
                </ul>
            </div>
            <div class="col-lg-7">
                <div class="card card-shadow">
                    <div class="card-body">
                        <form id="appointmentForm">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="name" id="patientName" class="form-control" pattern="[A-Za-z\s'-]+" title="Only letters, spaces, apostrophes, and hyphens are allowed" required>
                                    <small class="text-muted">Letters, spaces, apostrophes, and hyphens only</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Age</label>
                                    <input type="text" name="age" id="patientAge" class="form-control" pattern="[0-9]+" title="Only numbers are allowed" maxlength="3" required>
                                    <small class="text-muted">Numbers only</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="tel" name="phone" id="patientPhone" class="form-control" pattern="[0-9]+" title="Only numbers are allowed" required>
                                    <small class="text-muted">Numbers only</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Doctor</label>
                                    <select id="doctor_id" name="doctor_id" class="form-select" required>
                                        <option value="">Select a doctor...</option>
                                        <?php foreach ($doctors as $doctor): ?>
                                            <option value="<?= (int) $doctor['id']; ?>" <?= $selectedDoctorId === (int) $doctor['id'] ? 'selected' : ''; ?>>
                                                <?= e($doctor['name']); ?> - <?= e($doctor['specialty']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (empty($doctors)): ?>
                                        <small class="text-danger">No doctors available at the moment.</small>
                                    <?php endif; ?>
                                </div>
                                <div class="col-12">
                                    <div class="alert alert-info mb-0">
                                        Choose your preferred doctor and submit the request. Our admin or medical staff will assign the earliest available date and time, then confirm with you via phone/email.
                                    </div>
                                </div>
                                <div class="col-12 d-grid">
                                    <button class="btn btn-primary btn-lg" type="submit" <?= empty($doctors) ? 'disabled' : ''; ?>>Book Appointment</button>
                                </div>
                            </div>
                        </form>
                        <div id="appointmentAlert" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-6">
                <h3 class="fw-bold mb-3">How booking works</h3>
                <p class="text-muted">Every request flows straight to our admin team and the appropriate doctor for quick scheduling.</p>
                <ul class="list-unstyled">
                    <li class="mb-3">
                        <span class="badge bg-primary me-2">1</span>
                        Fill out the online booking form with your contact details and preferred doctor.
                    </li>
                    <li class="mb-3">
                        <span class="badge bg-primary me-2">2</span>
                        Admin verifies availability, assigns the earliest slot, and notifies the doctor.
                    </li>
                    <li class="mb-0">
                        <span class="badge bg-primary me-2">3</span>
                        You receive confirmation via phone/email; status updates show as Waiting, Confirmed, or Completed.
                    </li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="card card-shadow h-100">
                    <div class="card-body">
                        <h5 class="mb-3">What to expect next</h5>
                        <p class="text-muted mb-4">
                            Once the request lands in the admin dashboard, staff coordinate with the assigned doctor's calendar. Expect a callback or email within the day for most specialties.
                        </p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 h-100">
                                    <h6 class="mb-1">Status Tracking</h6>
                                    <p class="text-muted small mb-0">Waiting → Confirmed → Completed, all visible to staff.</p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 border rounded-3 h-100">
                                    <h6 class="mb-1">Calendar Sync</h6>
                                    <p class="text-muted small mb-0">Doctors review updates on their dashboard calendar instantly.</p>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info mt-4 mb-0">
                            Urgent case? Call (555) 999-0000 so we can fast-track your request.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>

