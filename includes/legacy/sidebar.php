<?php
$user = current_user();
?>
<aside class="sidebar">
    <div class="sidebar-top">
        <div>
            <div class="avatar">
                <?php if (!empty($user['picture_url'])): ?>
                    <img src="<?= e($user['picture_url']) ?>" alt="Profile" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                <?php else: ?>
                    <span style="font-size:36px;">👤</span>
                <?php endif; ?>
            </div>
            <div class="user-name" style="margin-top:8px;"><?= e($user['full_name'] ?? 'User') ?></div>
            <div class="user-role"><?= $user && $user['is_volunteer'] === 'Y' ? 'Volunteer' : 'Student' ?></div>
        </div>
    </div>

    <nav class="sidebar-menu">
        <ul class="nav-list">
            <li><a class="nav-box active" href="<?= BASE_URL ?>/profile.php">Profile Dashboard</a></li>
            <li><a class="nav-box" href="<?= BASE_URL ?>/edit_profile.php">Edit Profile</a></li>
            <li><a class="nav-box" href="<?= BASE_URL ?>/change_password.php">Change Password</a></li>
            <?php if ($user && $user['is_volunteer'] === 'Y'): ?>
                <li><a class="nav-box" href="<?= BASE_URL ?>/volunteer.php">Volunteer Profile</a></li>
            <?php else: ?>
                <li><a class="nav-box" href="<?= BASE_URL ?>/become_volunteer.php">Become a Volunteer</a></li>
            <?php endif; ?>
            <li><a class="nav-box" href="<?= BASE_URL ?>/logout.php">Logout</a></li>
        </ul>
    </nav>
</aside>
