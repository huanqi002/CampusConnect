<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/card_template.php';
require __DIR__ . '/../general/format.php';
require __DIR__ . '/../session_management/session_card.php';
require __DIR__ . '/functions.php';

requireLogin();

$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;

$session      = findSessionForFeedback($conn, $sessionId);
$alreadyGiven = hasFeedback($conn, $sessionId);

// Shown instead of the form when the session cannot be rated: [tone, title, message].
$blocked = null;
if (!$session || $session['user_id'] != $_SESSION['user_id']) {
    $blocked = ['Rejected', 'Session not found', 'This session does not exist or is not yours to rate.'];
} elseif ($session['status'] !== 'Completed') {
    $blocked = ['Cancelled', 'Feedback not available yet', 'You can only rate a session after it has been marked as completed.'];
} elseif ($alreadyGiven) {
    $blocked = ['Completed', 'Feedback already submitted', 'You have already rated this session. Thank you!'];
}

include __DIR__ . '/../general/header.php';
?>
<link rel="stylesheet" href="../../frontend/css/session.css?v=<?php echo filemtime(__DIR__ . '/../../frontend/css/session.css'); ?>">
<link rel="stylesheet" href="../../frontend/css/history.css?v=<?php echo filemtime(__DIR__ . '/../../frontend/css/history.css'); ?>">

<?php if ($blocked): [$tone, $title, $message] = $blocked; ?>
<div class="schedule-card session-view rate-view">
    <h2 class="view-title"><?php echo htmlspecialchars($title); ?></h2>
    <p class="status-note status-note-<?php echo $tone; ?>"><?php echo SESSION_CARD_ICONS[$tone === 'Completed' ? 'check' : 'clock']; ?><span><?php echo htmlspecialchars($message); ?></span></p>
    <div class="schedule-actions">
        <a class="btn btn-plain" href="history.php"><?php echo cardIcon('back'); ?>Back to History</a>
    </div>
</div>
<?php else:
    $volunteer = $session['volunteer_name'];
    $isOnline  = $session['support_mode'] === 'Online';
?>
<?php if (isset($_GET['err'])): ?>
<div class="message message-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<form class="schedule-card session-view rate-view" method="post" action="submit_feedback.php">
    <input type="hidden" name="session_id" value="<?php echo (int)$session['id']; ?>">

    <!-- Who and what is being rated -->
    <div class="rate-head">
        <div class="rate-summary">
            <?php echo personAvatar($volunteer); ?>
            <div class="rate-summary-body">
                <span class="person-role">Your volunteer</span>
                <strong><?php echo htmlspecialchars($volunteer); ?></strong>
            </div>
            <?php echo categoryChip($session['category']); ?>
        </div>
        <p class="rate-topic"><?php echo htmlspecialchars($session['description']); ?></p>
        <p class="rate-summary-meta">
            <?php if ($session['session_date'] !== null): ?>
            <span><?php echo SESSION_CARD_ICONS['calendar'] . htmlspecialchars(date('D, j M Y', strtotime($session['session_date']))); ?></span>
            <span><?php echo SESSION_CARD_ICONS['clock'] . htmlspecialchars(formatTimeRange($session['start_time'], $session['end_time'])); ?></span>
            <?php endif; ?>
            <span><?php echo SESSION_CARD_ICONS[$isOnline ? 'online' : 'inperson'] . ($isOnline ? 'Online' : 'Face-to-face'); ?></span>
        </p>
    </div>

    <!-- Star rating -->
    <section class="rate-stars-section">
        <h2 class="view-title">How helpful was this session?</h2>
        <p class="view-desc">Your rating helps <?php echo htmlspecialchars($volunteer); ?> and other students.</p>

        <fieldset class="star-picker" id="starPicker">
            <legend class="visually-hidden">Rating from 1 to 5 stars</legend>
            <?php for ($i = 1; $i <= 5; $i++): ?>
            <input type="radio" id="rating<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" required>
            <label class="star" for="rating<?php echo $i; ?>" title="<?php echo $i; ?> star<?php echo $i > 1 ? 's' : ''; ?>">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.8l2.84 5.76 6.36.92-4.6 4.48 1.09 6.33L12 17.3l-5.69 2.99 1.09-6.33-4.6-4.48 6.36-.92z"/></svg>
                <span class="visually-hidden"><?php echo $i; ?> star<?php echo $i > 1 ? 's' : ''; ?></span>
            </label>
            <?php endfor; ?>
        </fieldset>
        <div class="star-scale" aria-hidden="true"><span>Not helpful</span><span>Very helpful</span></div>
        <p class="star-caption" id="starCaption" aria-live="polite">Tap a star to rate</p>
    </section>

    <!-- Comments with quick phrases -->
    <section class="view-when">
        <label class="view-label" for="comments">Tell us more <span class="rate-optional">(optional)</span></label>
        <div class="rate-tags" id="rateTags" aria-label="Quick phrases">
            <?php foreach (['Explained clearly', 'Patient', 'Well prepared', 'On time', 'Friendly', 'Solved my problem'] as $tag): ?>
            <button type="button" class="rate-tag" aria-pressed="false"><?php echo $tag; ?></button>
            <?php endforeach; ?>
        </div>
        <textarea class="topic-input" id="comments" name="comments" rows="4" maxlength="500" placeholder="What went well? What could be better?"></textarea>
        <p class="rate-count"><span id="commentCount">0</span> / 500</p>
    </section>

    <div class="schedule-actions rate-actions">
        <a class="btn btn-plain" href="history.php"><?php echo cardIcon('back'); ?>Cancel</a>
        <button type="submit" class="btn btn-rate" id="rateSubmit"><span aria-hidden="true">&#9733;</span>Submit feedback</button>
    </div>
</form>

<script>
(function () {
    var words = ['Tap a star to rate', 'Not helpful', 'Could be better', 'Okay', 'Helpful', 'Very helpful'];
    var caption = document.getElementById('starCaption');
    var submit = document.getElementById('rateSubmit');
    var picker = document.getElementById('starPicker');
    var comments = document.getElementById('comments');
    var count = document.getElementById('commentCount');
    var checked = picker.querySelector('input:checked');
    submit.disabled = !checked;

    function show(value) {
        caption.textContent = value ? value + '/5 · ' + words[value] : words[0];
        caption.className = 'star-caption' + (value ? ' is-set is-' + value : '');
    }
    show(checked ? +checked.value : 0);

    picker.addEventListener('change', function (event) {
        show(+event.target.value);
        submit.disabled = false;
    });

    // Quick phrases add or remove "Phrase." in the comment box.
    function updateCount() { count.textContent = comments.value.length; }
    document.getElementById('rateTags').addEventListener('click', function (event) {
        var tag = event.target.closest('.rate-tag');
        if (!tag) return;
        var phrase = tag.textContent.trim() + '.';
        var on = tag.getAttribute('aria-pressed') !== 'true';
        var text = comments.value.replace(phrase, '').replace(/\s{2,}/g, ' ').trim();
        if (on) text = (text ? text + ' ' : '') + phrase;
        comments.value = text.slice(0, 500);
        tag.setAttribute('aria-pressed', on ? 'true' : 'false');
        updateCount();
    });
    comments.addEventListener('input', updateCount);
    updateCount();
})();
</script>
<?php endif; ?>

<?php include __DIR__ . '/../general/footer.php'; ?>
