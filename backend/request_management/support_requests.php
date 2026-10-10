<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';

requireLogin();

if (currentUser()['role'] !== 'volunteer') {
    header('Location: ../session_management/index.php?err=' . urlencode('Only volunteers can view support requests.'));
    exit;
}

include __DIR__ . '/../general/header.php';
?>

<h1 class="page-title">Support Request</h1>
<p class="subtitle">Support requests from students waiting for a volunteer. Accepting one creates a session.</p>

<div class="message message-success">This page is coming soon.</div>

<?php include __DIR__ . '/../general/footer.php'; ?>
