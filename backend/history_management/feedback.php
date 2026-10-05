<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/functions.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ../user_management/select_user.php');
    exit;
}

$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;

$session      = findSessionForFeedback($conn, $sessionId);
$alreadyGiven = hasFeedback($conn, $sessionId);

include __DIR__ . '/../general/header.php';

if (!$session || $session['user_id'] != $_SESSION['user_id']) {
    echo '<div class="message message-error">Session not found.</div>';
} elseif ($session['status'] !== 'Completed') {
    echo '<h1>Feedback not available yet</h1>';
    echo '<div class="message message-error">You can only rate a session after it has been marked as completed.</div>';
    echo '<a class="btn btn-plain" href="history.php">Back to history</a>';
} elseif ($alreadyGiven) {
    echo '<h1>Feedback already submitted</h1>';
    echo '<p>You have already rated this session. Thank you!</p>';
    echo '<a class="btn btn-plain" href="history.php">Back to history</a>';
} else {
?>
    <h1>Rate your session</h1>
    <p class="subtitle"><?php echo htmlspecialchars($session['category']); ?> with <?php echo htmlspecialchars($session['volunteer_name']); ?></p>

    <?php if (isset($_GET['err'])): ?>
    <div class="message message-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
    <?php endif; ?>

    <form method="post" action="submit_feedback.php">
        <input type="hidden" name="session_id" value="<?php echo (int)$session['id']; ?>">

        <label>How helpful was this session?</label>
        <div class="rating-choice">
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <label><input type="radio" name="rating" value="<?php echo $i; ?>" required> <?php echo $i; ?></label>
            <?php endfor; ?>
        </div>
        <p class="help-text">1 = not helpful, 5 = very helpful</p>

        <label for="comments">Comments (optional)</label>
        <textarea id="comments" name="comments" placeholder="What went well? What could be better?"></textarea>

        <div class="btn-row">
            <button type="submit" class="btn btn-primary">Submit feedback</button>
            <a class="btn btn-plain" href="history.php">Cancel</a>
        </div>
    </form>
<?php
}
include __DIR__ . '/../general/footer.php';
