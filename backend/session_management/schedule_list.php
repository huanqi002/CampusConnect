<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/card_template.php';
require __DIR__ . '/functions.php';

requireLogin();

$myId     = currentUser()['id'];
$sessions = fetchUnscheduledSessions($conn, $myId);

include __DIR__ . '/../general/header.php';
?>
<link rel="stylesheet" href="../../frontend/css/session.css">

<h1 class="page-title">Session Schedule</h1>

<?php if (isset($_GET['msg'])): ?>
<div class="message message-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['err'])): ?>
<div class="message message-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<?php if ($sessions->num_rows === 0): ?>
<p class="empty-state">No sessions waiting to be scheduled.</p>
<?php endif; ?>

<div class="card-list schedule-list">
<?php while ($s = $sessions->fetch_assoc()):
    $id        = (int)$s['id'];
    $isStudent = ($s['user_id'] == $myId);

    echo renderCard([
        'fields'  => [
            'Category'                           => $s['category'],
            'Description'                        => $s['description'],
            $isStudent ? 'Volunteer' : 'Student' => $isStudent ? $s['volunteer_name'] : $s['student_name'],
        ],
        'actions' => '<a class="btn btn-primary" href="schedule.php?session_id=' . $id . '">Schedule Now</a>',
    ]);
endwhile; ?>
</div>

<?php include __DIR__ . '/../general/footer.php'; ?>
