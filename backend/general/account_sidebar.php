<?php
$isVolunteer = ($user['is_volunteer'] ?? 'N') === 'Y';
$currentFile = basename($_SERVER['SCRIPT_NAME'] ?? 'profile.php');
$requestUrl = BASE_URL . ($isVolunteer ? '/backend/request_management/upcoming_request.php' : '/backend/request_management/request.php');
?>
<aside class="dashboard-sidebar">
    <a class="account-brand account-brand-light" href="<?= BASE_URL ?>/profile.php" aria-label="Campus Connect home"><img class="account-brand-logo" src="<?= BASE_URL ?>/frontend/images/campus-connect-logo.png" alt="Campus Connect"></a>
    <nav class="dashboard-side-nav" aria-label="Main navigation">
        <a class="<?= in_array($currentFile, ['profile.php', 'edit_profile.php', 'profile_picture.php', 'volunteer.php', 'change_password.php', 'verify_password_otp.php', 'set_new_password.php'], true) ? 'active' : '' ?>" href="<?= BASE_URL ?>/profile.php"><span class="side-nav-icon">P</span>My Profile</a>
        <a href="<?= e($requestUrl) ?>"><span class="side-nav-icon">R</span><?= $isVolunteer ? 'Upcoming Request' : 'Request' ?></a>
        <details class="dashboard-side-group" <?= str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/session_management/') ? 'open' : '' ?>>
            <summary><span class="side-nav-icon">S</span>Session</summary>
            <a href="<?= BASE_URL ?>/backend/session_management/index.php"><span class="side-nav-sub-icon">D</span>Dashboard</a>
            <a href="<?= BASE_URL ?>/backend/session_management/schedule_list.php"><span class="side-nav-sub-icon">C</span>Schedule</a>
            <?php if ($isVolunteer): ?><a href="<?= BASE_URL ?>/backend/session_management/volunteer_requests.php"><span class="side-nav-sub-icon">R</span>Request</a><?php endif; ?>
        </details>
        <a href="<?= BASE_URL ?>/backend/history_management/history.php"><span class="side-nav-icon">H</span>History</a>
    </nav>
    <a class="dashboard-side-logout" href="<?= BASE_URL ?>/logout.php" data-logout-trigger><span class="side-nav-icon">↪</span>Log out</a>
</aside>
<div class="logout-confirm-backdrop" id="logoutConfirm" role="dialog" aria-modal="true" aria-labelledby="logoutConfirmTitle" hidden>
    <section class="logout-confirm-box">
        <span class="logout-confirm-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg></span>
        <h2 id="logoutConfirmTitle">Ready to leave?</h2>
        <p>You can sign back in anytime to continue using Campus Connect.</p>
        <form class="logout-confirm-actions" method="post" action="<?= BASE_URL ?>/logout.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <button class="logout-confirm-cancel" type="button" id="logoutCancel">Stay here</button>
            <button class="logout-confirm-submit" type="submit">Log out</button>
        </form>
    </section>
</div>
<script>
(function () {
    var modal = document.getElementById('logoutConfirm');
    var cancel = document.getElementById('logoutCancel');
    if (!modal || !cancel) return;
    document.querySelectorAll('[data-logout-trigger]').forEach(function (link) {
        link.addEventListener('click', function (event) { event.preventDefault(); modal.hidden = false; cancel.focus(); });
    });
    function close() { modal.hidden = true; }
    cancel.addEventListener('click', close);
    modal.addEventListener('click', function (event) { if (event.target === modal) close(); });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && !modal.hidden) close(); });
})();
</script>
<div class="logout-confirm-backdrop" id="actionConfirm" role="dialog" aria-modal="true" aria-labelledby="actionConfirmTitle" hidden>
    <section class="logout-confirm-box">
        <span class="logout-confirm-icon action-confirm-icon" id="actionConfirmIcon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M16 3l4 4-4 4M20 7H8a4 4 0 0 0-4 4M8 21l-4-4 4-4M4 17h12a4 4 0 0 0 4-4"/></svg></span>
        <h2 id="actionConfirmTitle"></h2>
        <p id="actionConfirmMessage"></p>
        <div class="logout-confirm-actions"><button class="logout-confirm-cancel" type="button" id="actionConfirmCancel">Cancel</button><button class="logout-confirm-submit" type="button" id="actionConfirmSubmit"></button></div>
    </section>
</div>
<script>
(function () {
    var modal = document.getElementById('actionConfirm');
    var cancel = document.getElementById('actionConfirmCancel');
    var confirm = document.getElementById('actionConfirmSubmit');
    var pending;
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!form.matches('form[data-confirm-title]')) return;
        event.preventDefault(); pending = form;
        document.getElementById('actionConfirmTitle').textContent = form.dataset.confirmTitle;
        document.getElementById('actionConfirmMessage').textContent = form.dataset.confirmMessage;
        confirm.textContent = form.dataset.confirmSubmit || 'Confirm';
        var tone = form.dataset.confirmIcon || 'switch';
        document.getElementById('actionConfirmIcon').className = 'logout-confirm-icon action-confirm-icon is-' + tone;
        confirm.className = 'logout-confirm-submit is-' + tone;
        var greenTone = tone === 'switch' || tone === 'active';
        confirm.style.setProperty('background-color', greenTone ? '#209957' : '#d94350', 'important');
        confirm.style.setProperty('border-color', greenTone ? '#18834d' : '#c62836', 'important');
        var icon = document.getElementById('actionConfirmIcon');
        icon.style.setProperty('color', greenTone ? '#16824c' : '#c62836', 'important');
        icon.style.setProperty('background-color', greenTone ? '#e9f8ef' : '#fff0f1', 'important');
        modal.hidden = false; cancel.focus();
    });
    function close() { modal.hidden = true; pending = null; }
    cancel.addEventListener('click', close);
    confirm.addEventListener('click', function () { var form = pending; close(); if (form) form.submit(); });
    modal.addEventListener('click', function (event) { if (event.target === modal) close(); });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && !modal.hidden) close(); });
})();
</script>
