<?php
if (empty($DEV_TOOLS)) {
    return;
}

require_once __DIR__ . '/../user_management/functions.php';

$devUsers = listUsers($conn);
?>
<div class="dev-switcher">
    <p class="dev-switcher-title">Test: switch user</p>
    <?php while ($devUser = $devUsers->fetch_assoc()): ?>
    <a class="dev-user<?php echo $devUser['id'] == ($_SESSION['user_id'] ?? null) ? ' current' : ''; ?>"
       href="../user_management/set_user.php?id=<?php echo (int)$devUser['id']; ?>">
        <span><?php echo htmlspecialchars($devUser['name']); ?></span>
        <span class="role-tag"><?php echo htmlspecialchars(ucfirst($devUser['role'])); ?></span>
    </a>
    <?php endwhile; ?>
</div>
