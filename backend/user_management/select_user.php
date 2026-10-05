<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/functions.php';
$result = listUsers($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Campus Connect - Select User</title>
<link rel="stylesheet" href="../../frontend/css/style.css">
</head>
<body>
<header class="topbar">
    <span class="brand">Campus Connect</span>
</header>
<main class="page">
    <h1>Who are you?</h1>
    <p class="subtitle">Choose your name to continue. (In the full system this would be a login page.)</p>
    <div class="user-list">
        <?php while ($row = $result->fetch_assoc()): ?>
        <a class="user-pick" href="set_user.php?id=<?php echo (int)$row['id']; ?>">
            <span><?php echo htmlspecialchars($row['name']); ?></span>
            <span class="role-tag"><?php echo htmlspecialchars(ucfirst($row['role'])); ?></span>
        </a>
        <?php endwhile; ?>
    </div>
    <p class="subtitle">Don't see your name? <a href="register.php">Register a new identity</a>.</p>
</main>
</body>
</html>
