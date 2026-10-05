<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';

requireLogin();

include __DIR__ . '/../general/header.php';
?>

<h1>My profile</h1>
<p class="subtitle">View and update your account details.</p>

<div class="message message-success">This page is coming soon.</div>

<?php include __DIR__ . '/../general/footer.php'; ?>
