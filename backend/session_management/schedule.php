<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/functions.php';

requireLogin();

$myId      = currentUser()['id'];
$sessionId = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;
$session   = findSessionWithStatus($conn, $sessionId, 'Pending');

if (!$session || ($session['user_id'] != $myId && $session['volunteer_id'] != $myId)) {
    header('Location: schedule_list.php?err=' . urlencode('This session is not ready to be scheduled.'));
    exit;
}

include __DIR__ . '/../general/header.php';
?>

<h1>Book a session</h1>
<p class="subtitle"><?php echo htmlspecialchars($session['category']); ?> &mdash;
   Volunteer: <?php echo htmlspecialchars($session['volunteer_name']); ?></p>

<?php if (isset($_GET['err'])): ?>
<div class="message message-error"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<form id="scheduleForm" method="post" action="confirm_session.php">
    <input type="hidden" name="session_id" value="<?php echo (int)$session['id']; ?>">

    <label for="session_date">Choose a date</label>
    <input type="date" id="session_date" name="session_date" min="<?php echo date('Y-m-d'); ?>" required>
    <p class="help-text">Available times for this volunteer will appear once you pick a date.</p>

    <label for="session_time">Choose a time</label>
    <select id="session_time" name="session_time" required>
        <option value="">Pick a date first</option>
    </select>
    <p class="help-text" id="slotHelp"></p>

    <label>Choose how the session will happen</label>
    <div class="radio-group">
        <label><input type="radio" name="mode" value="Online" <?php echo $session['support_mode'] !== 'Face-to-face' ? 'checked' : ''; ?>> Online</label>
        <label><input type="radio" name="mode" value="Face-to-face" <?php echo $session['support_mode'] === 'Face-to-face' ? 'checked' : ''; ?>> Face-to-face</label>
    </div>

    <div class="btn-row">
        <button type="submit" class="btn btn-primary">Confirm session</button>
        <a class="btn btn-plain" href="schedule_list.php">Cancel</a>
    </div>
</form>

<script src="../../frontend/js/script.js"></script>
<script>
    initScheduleForm(<?php echo (int)$session['volunteer_id']; ?>);
</script>

<?php include __DIR__ . '/../general/footer.php'; ?>
