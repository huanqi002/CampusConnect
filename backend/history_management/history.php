<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/card_template.php';
require __DIR__ . '/functions.php';

requireLogin();

$myId    = currentUser()['id'];
$entries = fetchHistory($conn, $myId);

include __DIR__ . '/../general/header.php';
?>

<h1 class="page-title">History</h1>

<?php if (isset($_GET['msg'])): ?>
<div class="message message-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['err'])): ?>
<div class="message message-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<?php if ($entries->num_rows === 0): ?>
<p class="empty-state">No completed or cancelled sessions yet.</p>
<?php endif; ?>

<div class="card-list">
<?php while ($e = $entries->fetch_assoc()):
    $id = (int)$e['session_id'];

    echo renderCard([
        'href'    => "history_details.php?session_id=$id",
        'fields'  => [
            'Category'    => $e['category'],
            'Description' => $e['description'],
            'Date'        => $e['session_date'] ? date('D, j M Y', strtotime($e['session_date'])) : 'Not booked',
        ],
        'status'  => $e['final_status'],
        'actions' => canRate($e, $myId)
            ? '<a class="btn btn-primary" href="feedback.php?session_id=' . $id . '">Rate</a>'
            : ($e['rating'] ? '<span class="card-rating">' . (int)$e['rating'] . ' / 5</span>' : ''),
    ]);
endwhile; ?>
</div>

<?php include __DIR__ . '/../general/footer.php'; ?>
