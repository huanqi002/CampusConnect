<?php

const USER_COLUMNS = "id, full_name AS name, email,
                      IF(is_volunteer = 'Y', 'volunteer', 'student') AS role";

function listUsers(mysqli $conn): mysqli_result
{
    return $conn->query("SELECT " . USER_COLUMNS . " FROM users
                         WHERE is_active = 'Y'
                         ORDER BY is_volunteer, full_name");
}

function findUserById(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare("SELECT " . USER_COLUMNS . " FROM users WHERE id = ? AND is_active = 'Y'");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}

function emailExists(mysqli $conn, string $email): bool
{
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_assoc();
}

function loginAsUser(array $user): void
{
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role']    = $user['role'];
    $_SESSION['name']    = $user['name'];
}

function logoutUser(): void
{
    session_destroy();
}

function registerUser(mysqli $conn, string $name, string $email, string $university,
                      string $password, bool $isVolunteer): array
{
    $hash      = password_hash($password, PASSWORD_DEFAULT);
    $volunteer = $isVolunteer ? 'Y' : 'N';

    $stmt = $conn->prepare("INSERT INTO users (full_name, email, university_name, password_hash, is_volunteer)
                             VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sssss', $name, $email, $university, $hash, $volunteer);
    $stmt->execute();

    return ['id' => $conn->insert_id, 'name' => $name, 'role' => $isVolunteer ? 'volunteer' : 'student'];
}
