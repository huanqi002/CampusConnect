<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/card_template.php';
require __DIR__ . '/../general/format.php';
require __DIR__ . '/functions.php';

requireLogin();

$myId     = currentUser()['id'];
$sessions = fetchActiveSessions($conn, $myId);

include __DIR__ . '/../general/header.php';
?>

<h1 class="page-title">Schedule</h1>

<?php if (isset($_GET['msg'])): ?>
<div class="message message-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['err'])): ?>
<div class="message message-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<?php if ($sessions->num_rows === 0): ?>
<p class="empty-state">You have no sessions to schedule.</p>
<?php endif; ?>

<div class="card-list">
<?php while ($s = $sessions->fetch_assoc()):
    $id        = (int)$s['id'];
    $isStudent = ($s['user_id'] == $myId);
    $when      = $s['status'] === 'Pending'
        ? 'Not booked yet'
        : date('D, j M Y', strtotime($s['session_date'])) . ', ' . formatTime($s['start_time']) . ' (' . $s['support_mode'] . ')';

    echo renderCard([
        'href'    => "session_details.php?session_id=$id",
        'fields'  => [
            'Category'                             => $s['category'],
            $isStudent ? 'Volunteer' : 'Student'   => $isStudent ? $s['volunteer_name'] : $s['student_name'],
            'Date and time'                        => $when,
        ],
        'status'  => sessionDisplayStatus($s),
        'actions' => $s['status'] === 'Pending'
            ? '<a class="btn btn-primary" href="schedule.php?session_id=' . $id . '">Book</a>'
            : '',
    ]);
endwhile; ?>
</div>

<?php include __DIR__ . '/../general/footer.php'; ?>
