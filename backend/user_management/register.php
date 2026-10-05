<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/functions.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name        = trim($_POST['name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $university  = trim($_POST['university_name'] ?? '');
    $password    = $_POST['password'] ?? '';
    $isVolunteer = isset($_POST['is_volunteer']);

    if ($name === '' || $university === '' || $password === '') {
        $errors[] = 'Please fill in every field.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (emailExists($conn, $email)) {
        $errors[] = 'An account with this email already exists.';
    }

    if (!$errors) {
        $user = registerUser($conn, $name, $email, $university, $password, $isVolunteer);
        loginAsUser($user);
        header('Location: ../session_management/index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Campus Connect - Register</title>
<link rel="stylesheet" href="../../frontend/css/style.css">
</head>
<body>
<header class="topbar">
    <span class="brand">Campus Connect</span>
</header>
<main class="page">
    <h1>Register a new identity</h1>
    <p class="subtitle">(In the full system this would create a real account. Here it just adds a sample user.)</p>

    <?php foreach ($errors as $error): ?>
    <div class="message message-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endforeach; ?>

    <form method="post" action="register.php">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>

        <label for="university_name">University</label>
        <input type="text" id="university_name" name="university_name" value="<?php echo htmlspecialchars($_POST['university_name'] ?? ''); ?>" required>

        <label for="password">Password</label>
        <input type="password" id="password" name="password" required>

        <div class="radio-group">
            <label><input type="checkbox" name="is_volunteer" value="Y" <?php echo isset($_POST['is_volunteer']) ? 'checked' : ''; ?>> I also want to volunteer</label>
        </div>

        <div class="btn-row">
            <button type="submit" class="btn btn-primary">Register</button>
            <a class="btn btn-plain" href="select_user.php">Cancel</a>
        </div>
    </form>
</main>
</body>
</html>
