<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/card_template.php';
require __DIR__ . '/../general/format.php';
require __DIR__ . '/../session_management/session_card.php';
require __DIR__ . '/functions.php';

requireLogin();

$myId    = currentUser()['id'];
$entries = fetchHistory($conn, $myId);

include __DIR__ . '/../general/header.php';
?>
<link rel="stylesheet" href="../../frontend/css/session.css">
<link rel="stylesheet" href="../../frontend/css/history.css">

<?php if (isset($_GET['msg'])): ?>
<div class="message message-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>
<?php if (isset($_GET['err'])): ?>
<div class="message message-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<?php if ($entries->num_rows === 0): ?>
<p class="empty-state">No completed or cancelled sessions yet.</p>
<?php endif; ?>

<div class="card-list history-list">
<?php while ($e = $entries->fetch_assoc()):
    $id = (int)$e['session_id'];

    if (canRate($e, $myId)) {
        $actions = '<a class="btn btn-rate" href="feedback.php?session_id=' . $id . '">'
                 . '<span aria-hidden="true">&#9733;</span>Rate Now </a>';
    } elseif ($e['rating']) {
        $rating  = (int)$e['rating'];
        $actions = '<span class="card-rating" title="Rated ' . $rating . ' out of 5" aria-label="Rated ' . $rating . ' out of 5">'
                 . '<span class="card-stars" aria-hidden="true">'
                 . str_repeat('<span class="is-on">&#9733;</span>', $rating) . str_repeat('<span>&#9733;</span>', 5 - $rating)
                 . '</span><strong>' . $rating . '/5</strong></span>';
    } else {
        $actions = '';
    }

    echo renderCard([
        'href'     => "history_details.php?session_id=$id",
        'textHtml' => sessionCardText($e, (int)$myId, 'Not booked'),
        'status'   => $e['final_status'],
        'actions'  => $actions,
    ]);
endwhile; ?>
</div>

<?php include __DIR__ . '/../general/footer.php'; ?>
