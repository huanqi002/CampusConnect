<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/card_template.php';
require __DIR__ . '/../general/format.php';
require __DIR__ . '/functions.php';

requireLogin();

if (currentUser()['role'] !== 'volunteer') {
    header('Location: index.php?err=' . urlencode('Only volunteers can view session requests.'));
    exit;
}

$myId     = currentUser()['id'];
$requests = fetchSessionRequests($conn, $myId);

include __DIR__ . '/../general/header.php';
?>
<link rel="stylesheet" href="../../frontend/css/session.css">

<h1 class="page-title">Session Request</h1>

<?php if (isset($_GET['msg'])): ?>
<div class="message message-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['err'])): ?>
<div class="message message-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<?php if ($requests->num_rows === 0): ?>
<p class="empty-state">No sessions waiting for you to accept.</p>
<?php endif; ?>

<div class="card-list">
<?php while ($s = $requests->fetch_assoc()):
    $id = (int)$s['id'];

    echo renderCard([
        'href'    => "session_details.php?session_id=$id",
        'fields'  => [
            'Category'      => $s['category'],
            'Description'   => $s['description'],
            'Student'       => $s['student_name'],
            'Date and time' => date('D, j M Y', strtotime($s['session_date'])) . ', ' . formatTime($s['start_time']) . ' (' . $s['support_mode'] . ')',
        ],
        'status'  => 'Pending',
    ]);
endwhile; ?>
</div>

<?php include __DIR__ . '/../general/footer.php'; ?>
