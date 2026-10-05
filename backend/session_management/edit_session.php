<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/functions.php';

requireLogin();

$myId      = currentUser()['id'];
$sessionId = (int)($_POST['session_id'] ?? $_GET['session_id'] ?? 0);
$session   = findSessionWithStatus($conn, $sessionId, 'Pending');

if (!$session || ($session['user_id'] != $myId && $session['volunteer_id'] != $myId)) {
    header('Location: index.php?err=' . urlencode('Only a Pending session can be edited.'));
    exit;
}

$errors      = [];
$category    = $session['category'];
$description = $session['description'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category    = $_POST['category'] ?? '';
    $description = trim($_POST['description'] ?? '');

    if (!in_array($category, SESSION_CATEGORIES, true)) {
        $errors[] = 'Please choose a category.';
    }
    if ($description === '' || mb_strlen($description) > 500) {
        $errors[] = 'Please enter a description of up to 500 characters.';
    }

    if (!$errors) {
        updateSessionDetails($conn, $sessionId, $category, $description);
        header('Location: index.php?msg=' . urlencode('Session details updated.'));
        exit;
    }
}

include __DIR__ . '/../general/header.php';
?>

<h1>Edit session</h1>
<p class="subtitle">You can change the category and description until the session is scheduled.</p>

<?php foreach ($errors as $error): ?>
<div class="message message-error"><?php echo htmlspecialchars($error); ?></div>
<?php endforeach; ?>

<form method="post" action="edit_session.php">
    <input type="hidden" name="session_id" value="<?php echo (int)$session['id']; ?>">

    <label for="category">Category</label>
    <select id="category" name="category" required>
        <?php foreach (SESSION_CATEGORIES as $option): ?>
        <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $option === $category ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
        <?php endforeach; ?>
    </select>

    <label for="description">Description</label>
    <textarea id="description" name="description" maxlength="500" required><?php echo htmlspecialchars($description); ?></textarea>

    <div class="btn-row">
        <button type="submit" class="btn btn-primary">Save changes</button>
        <a class="btn btn-plain" href="index.php">Cancel</a>
    </div>
</form>

<?php include __DIR__ . '/../general/footer.php'; ?>
