<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_admin_or_staff();

$month = isset($_GET['month']) ? (int) $_GET['month'] : (int) date('n');
$year = isset($_GET['year']) ? (int) $_GET['year'] : (int) date('Y');

if ($month < 1) {
    $month = 1;
} elseif ($month > 12) {
    $month = 12;
}

$current = DateTime::createFromFormat('Y-n-j', sprintf('%04d-%02d-01', max(1970, $year), $month));
$daysInMonth = (int) $current->format('t');
$firstWeekday = (int) $current->format('w'); // 0 (Sun) - 6 (Sat)

$startRange = $current->format('Y-m-01');
$endRange = $current->format('Y-m-t');

$db = get_db();
$stmt = $db->prepare(
    'SELECT a.date, a.time, a.status, p.name AS patient_name, d.name AS doctor_name
     FROM appointments a
     JOIN patients p ON p.id = a.patient_id
     JOIN doctors d ON d.id = a.doctor_id
     WHERE a.date BETWEEN ? AND ?
     ORDER BY a.date ASC, a.time ASC'
);
$stmt->execute([$startRange, $endRange]);
$appointments = $stmt->fetchAll();

$appointmentsByDate = [];
foreach ($appointments as $appointment) {
    $appointmentsByDate[$appointment['date']][] = $appointment;
}

function buildNavUrl(int $month, int $year): string
{
    return '/admin/calendar.php?month=' . $month . '&year=' . $year;
}

$prevMonthDate = clone $current;
$prevMonthDate->modify('-1 month');
$nextMonthDate = clone $current;
$nextMonthDate->modify('+1 month');

$prevYearDate = clone $current;
$prevYearDate->modify('-1 year');
$nextYearDate = clone $current;
$nextYearDate->modify('+1 year');

$pageTitle = 'Calendar';
$activePage = 'calendar';
include __DIR__ . '/partials/header.php';
?>
<div class="card card-shadow mb-4">
    <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div>
            <h4 class="mb-0"><?= e($current->format('F Y')); ?></h4>
            <small class="text-muted">Tap a date to view appointments.</small>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-secondary" href="<?= e(buildNavUrl((int) $prevYearDate->format('n'), (int) $prevYearDate->format('Y'))); ?>">« Prev Year</a>
            <a class="btn btn-outline-primary" href="<?= e(buildNavUrl((int) $prevMonthDate->format('n'), (int) $prevMonthDate->format('Y'))); ?>">‹ Prev Month</a>
            <a class="btn btn-outline-primary" href="<?= e(buildNavUrl((int) $nextMonthDate->format('n'), (int) $nextMonthDate->format('Y'))); ?>">Next Month ›</a>
            <a class="btn btn-outline-secondary" href="<?= e(buildNavUrl((int) $nextYearDate->format('n'), (int) $nextYearDate->format('Y'))); ?>">Next Year »</a>
        </div>
    </div>
</div>

<div class="card card-shadow">
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
                $badge = $hasAppointments ? '<span class="badge bg-primary badge-status">' . count($appointmentsByDate[$dateString]) . ' appt</span>' : '';
                echo '<div class="calendar-cell' . ($hasAppointments ? '' : ' bg-light-subtle') . '" data-date="' . e($dateString) . '">';
                echo '<div class="d-flex justify-content-between align-items-start">';
                echo '<strong>' . $day . '</strong>';
                echo $badge;
                echo '</div>';
                if ($hasAppointments) {
                    echo '<ul class="list-unstyled small mt-2 mb-0">';
                    $preview = array_slice($appointmentsByDate[$dateString], 0, 2);
                    foreach ($preview as $item) {
                        $timePreview = $item['time'] ? date('h:i A', strtotime($item['time'])) : 'Time TBD';
                        echo '<li>' . e($item['patient_name']) . ' — ' . e($timePreview) . '</li>';
                    }
                    if (count($appointmentsByDate[$dateString]) > 2) {
                        echo '<li class="text-muted">+ more...</li>';
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
                <h5 class="mb-3" id="selectedDateTitle">Select a date</h5>
                <div id="appointmentList" class="list-group small">
                    <div class="text-muted">Click any date on the calendar to see its appointments, including full history.</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const appointmentsData = <?= json_encode($appointmentsByDate); ?>;
    const cells = document.querySelectorAll('.calendar-cell');
    const title = document.getElementById('selectedDateTitle');
    const list = document.getElementById('appointmentList');

    cells.forEach(cell => {
        if (cell.classList.contains('disabled')) return;
        cell.addEventListener('click', () => {
            const date = cell.dataset.date;
            const entries = appointmentsData[date] ?? [];
            const [y, m, d] = date.split('-').map(Number);
            title.textContent = new Date(y, (m - 1), d).toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            list.innerHTML = '';
            if (!entries.length) {
                list.innerHTML = '<div class="text-muted">No appointments found for this date.</div>';
                return;
            }
            entries.forEach(item => {
                const badgeClass = item.status === 'Waiting' ? 'warning' : (item.status === 'Confirmed' ? 'success' : 'secondary');
                const row = document.createElement('div');
                row.className = 'list-group-item list-group-item-action flex-column align-items-start';
                const timeText = item.time
                    ? new Date('1970-01-01T' + item.time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                    : 'Time TBD';
                row.innerHTML = `
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">${item.patient_name} with ${item.doctor_name}</h6>
                        <span class="badge text-bg-${badgeClass}">${item.status}</span>
                    </div>
                    <small>${timeText}</small>
                `;
                list.appendChild(row);
            });
        });
    });
</script>
<?php include __DIR__ . '/partials/footer.php'; ?>

