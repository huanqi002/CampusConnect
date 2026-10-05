<?php

function requireLogin(): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: ../user_management/select_user.php');
        exit;
    }
}

function currentUser(): array
{
    return [
        'id'   => $_SESSION['user_id'] ?? null,
        'role' => $_SESSION['role'] ?? null,
        'name' => $_SESSION['name'] ?? null,
    ];
}
