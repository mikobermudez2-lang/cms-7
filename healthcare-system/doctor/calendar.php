<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_doctor();

$doctorId = $_SESSION['doctor_id'] ?? get_doctor_id_for_user(current_user()['username']);
$_SESSION['doctor_id'] = $doctorId;

$month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('n');
$year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');
$month = max(1, min(12, $month));

$current = DateTime::createFromFormat('Y-n-j', sprintf('%04d-%02d-01', max(1970, $year), $month));
$daysInMonth = (int) $current->format('t');
$firstWeekday = (int) $current->format('w');

$startRange = $current->format('Y-m-01');
$endRange = $current->format('Y-m-t');

$db = get_db();
$stmt = $db->prepare(
    'SELECT a.date, a.time, a.status, p.name AS patient_name
     FROM appointments a
     JOIN patients p ON p.id = a.patient_id
     WHERE a.doctor_id = ? AND a.date BETWEEN ? AND ?
     ORDER BY a.date ASC, a.time ASC'
);
$stmt->execute([$doctorId, $startRange, $endRange]);
$appointments = $stmt->fetchAll();

$appointmentsByDate = [];
foreach ($appointments as $appointment) {
    $appointmentsByDate[$appointment['date']][] = $appointment;
}

function doctorCalendarNav(int $month, int $year): string
{
    return '/doctor/calendar.php?month=' . $month . '&year=' . $year;
}

$prevMonthDate = (clone $current)->modify('-1 month');
$nextMonthDate = (clone $current)->modify('+1 month');
$prevYearDate = (clone $current)->modify('-1 year');
$nextYearDate = (clone $current)->modify('+1 year');

$pageTitle = 'Calendar';
$activePage = 'calendar';
include __DIR__ . '/partials/header.php';
?>
<div class="alert alert-info">
    Calendar is read-only for past appointments. Click a date to review full history.
</div>
<div class="card card-shadow mb-3">
    <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h4 class="mb-0"><?= e($current->format('F Y')); ?></h4>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-secondary" href="<?= e(doctorCalendarNav((int) $prevYearDate->format('n'), (int) $prevYearDate->format('Y'))); ?>">« Prev Year</a>
            <a class="btn btn-outline-primary" href="<?= e(doctorCalendarNav((int) $prevMonthDate->format('n'), (int) $prevMonthDate->format('Y'))); ?>">‹ Prev Month</a>
            <a class="btn btn-outline-primary" href="<?= e(doctorCalendarNav((int) $nextMonthDate->format('n'), (int) $nextMonthDate->format('Y'))); ?>">Next Month ›</a>
            <a class="btn btn-outline-secondary" href="<?= e(doctorCalendarNav((int) $nextYearDate->format('n'), (int) $nextYearDate->format('Y'))); ?>">Next Year »</a>
        </div>
    </div>
</div>

<div class="card card-shadow mb-4">
    <div class="card-body">
        <div class="calendar-grid mb-4">
            <?php
            $weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            foreach ($weekdays as $dayLabel) {
                echo '<div class="text-center fw-semibold text-muted">' . e($dayLabel) . '</div>';
            }
            for ($i = 0; $i < $firstWeekday; $i++) {
                echo '<div class="calendar-cell disabled"></div>';
            }
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $dateString = $current->format('Y-m-') . str_pad((string) $day, 2, '0', STR_PAD_LEFT);
                $hasAppointments = isset($appointmentsByDate[$dateString]);
                $cellClasses = 'calendar-cell';
                $cellClasses .= $hasAppointments ? '' : ' bg-light-subtle';
                echo '<div class="' . $cellClasses . '" data-date="' . e($dateString) . '">';
                echo '<div class="d-flex justify-content-between align-items-start">';
                echo '<strong>' . $day . '</strong>';
                if ($hasAppointments) {
                    echo '<span class="badge bg-primary badge-status">' . count($appointmentsByDate[$dateString]) . '</span>';
                }
                echo '</div>';
                if ($hasAppointments) {
                    echo '<ul class="list-unstyled small mt-2 mb-0">';
                    foreach (array_slice($appointmentsByDate[$dateString], 0, 3) as $item) {
                        $timePreview = $item['time'] ? date('h:i A', strtotime($item['time'])) : 'Time TBD';
                        echo '<li>' . e($item['patient_name']) . ' — ' . e($timePreview) . '</li>';
                    }
                    echo '</ul>';
                } else {
                    echo '<p class="text-muted small mt-3 mb-0">No appointments</p>';
                }
                echo '</div>';
            }
            ?>
        </div>
        <div class="card card-shadow">
            <div class="card-body">
                <h5 class="mb-3" id="doctorSelectedDate">Select a date</h5>
                <div id="doctorAppointmentList" class="list-group small">
                    <div class="text-muted">Click any date for detailed appointments (past dates read-only).</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const docAppointments = <?= json_encode($appointmentsByDate); ?>;
    const docCells = document.querySelectorAll('.calendar-cell');
    const docTitle = document.getElementById('doctorSelectedDate');
    const docList = document.getElementById('doctorAppointmentList');

    docCells.forEach(cell => {
        if (cell.classList.contains('disabled')) return;
        cell.addEventListener('click', () => {
            const date = cell.dataset.date;
            const [y, m, d] = date.split('-').map(Number);
            docTitle.textContent = new Date(y, (m - 1), d).toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            const entries = docAppointments[date] ?? [];
            docList.innerHTML = '';
            if (!entries.length) {
                docList.innerHTML = '<div class="text-muted">No appointments for this date.</div>';
                return;
            }
            entries.forEach(entry => {
                const badgeClass = entry.status === 'Confirmed' ? 'success' : (entry.status === 'Waiting' ? 'warning' : 'secondary');
                const row = document.createElement('div');
                row.className = 'list-group-item';
                const timeText = entry.time
                    ? new Date('1970-01-01T' + entry.time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                    : 'Time TBD';
                row.innerHTML = `
                    <div class="d-flex justify-content-between">
                        <strong>${entry.patient_name}</strong>
                        <span class="badge text-bg-${badgeClass}">${entry.status}</span>
                    </div>
                    <small>${timeText}</small>
                `;
                docList.appendChild(row);
            });
        });
    });
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>

