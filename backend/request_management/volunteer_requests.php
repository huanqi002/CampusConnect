<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';

requireLogin();

if (currentUser()['role'] !== 'volunteer') {
    header('Location: ../session_management/index.php?err=' . urlencode('Only volunteers can view session requests.'));
    exit;
}

include __DIR__ . '/../general/header.php';
?>

<h1 class="page-title">Session Request</h1>
<p class="subtitle">Requests from students waiting for a volunteer. Accepting one creates a session.</p>

<div class="message message-success">This page is coming soon.</div>

<?php include __DIR__ . '/../general/footer.php'; ?>
