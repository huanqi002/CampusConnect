<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../session_management/functions.php';

requireLogin();

$user = currentUser();
$requestId = isset($_POST['request_id']) ? (int) $_POST['request_id'] : 0;
$response = $_POST['response'] ?? '';

if ($user['role'] !== 'volunteer' || $requestId <= 0 || !in_array($response, ['Accepted', 'Rejected'], true)) {
    header('Location: upcoming_request.php?err=' . urlencode('Invalid request response.'));
    exit;
}

$volunteerId = (int) $user['id'];
$conn->begin_transaction();
try {
    $stmt = $conn->prepare("
        SELECT id, user_id, volunteer_id, category, description, support_mode, status
        FROM requests
        WHERE id = ? AND volunteer_id = ? AND status = 'Pending'
        FOR UPDATE
    ");
    $stmt->bind_param('ii', $requestId, $volunteerId);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$request) {
        $conn->rollback();
        header('Location: upcoming_request.php?err=' . urlencode('This request is no longer waiting for your response.'));
        exit;
    }

    $update = $conn->prepare("UPDATE requests SET status = ?, responded_at = NOW() WHERE id = ? AND volunteer_id = ? AND status = 'Pending'");
    $update->bind_param('sii', $response, $requestId, $volunteerId);
    $update->execute();
    $updated = $update->affected_rows === 1;
    $update->close();

    if (!$updated) {
        $conn->rollback();
        header('Location: upcoming_request.php?err=' . urlencode('This request has already been responded to.'));
        exit;
    }

    if ($response === 'Accepted') {
        createUnscheduledSession($conn, $request);
    }

    $outcome = strtolower($response);
    $volunteerName = (string) $user['name'];
    $description = (string) $request['description'];
    $statusSuffix = $response === 'Rejected' ? "\nPlease re-select another volunteer." : '';
    $noticeType = 'Status Update';
    $prefix = "Your {$request['category']} request for “";
    $suffix = "” has been {$outcome} by volunteer {$volunteerName}.{$statusSuffix}";
    $notification = $conn->prepare("INSERT INTO notifications (user_id, message, notice_type, is_read, created_at) VALUES (?, ?, ?, 'N', NOW())");
    $descriptionLimit = max(0, 500 - mb_strlen($prefix) - mb_strlen($suffix));
    $noticeDescription = mb_substr($description, 0, $descriptionLimit);
    if (mb_strlen($description) > $descriptionLimit && $descriptionLimit > 0) {
        $noticeDescription = mb_substr($noticeDescription, 0, $descriptionLimit - 1) . '…';
    }
    $notificationMessage = $prefix . $noticeDescription . $suffix;
    $notification->bind_param('iss', $request['user_id'], $notificationMessage, $noticeType);
    $notification->execute();
    $notification->close();

    $conn->commit();
} catch (Throwable $exception) {
    $conn->rollback();
    error_log('Failed to respond to support request ' . $requestId . ': ' . $exception->getMessage());
    header('Location: upcoming_request.php?err=' . urlencode('The request could not be processed. Please try again.'));
    exit;
}

header('Location: upcoming_request.php');
exit;
