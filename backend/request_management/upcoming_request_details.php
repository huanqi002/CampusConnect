<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../general/card_template.php';
require __DIR__ . '/../general/format.php';
require __DIR__ . '/../session_management/result_modal.php';

requireLogin();

$user = currentUser();
if ($user['role'] !== 'volunteer') {
    header('Location: ../session_management/index.php?err=' . urlencode('Only volunteers can view assigned support requests.'));
    exit;
}

$requestId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$volunteerId = (int) $user['id'];
$stmt = $conn->prepare("
    SELECT r.id, r.user_id, r.category, r.description, r.preferred_day, r.start_time,
           r.end_time, r.support_mode, r.status,
           u.full_name AS student_name, u.email AS student_email
    FROM requests r
    JOIN users u ON u.id = r.user_id
    WHERE r.id = ? AND r.volunteer_id = ?
");
$stmt->bind_param('ii', $requestId, $volunteerId);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$request) {
    header('Location: upcoming_request.php?err=' . urlencode('This request is no longer assigned to you.'));
    exit;
}

$headerTitle = 'Upcoming Details';
$headerBack = ['upcoming_request.php', 'Back to upcoming requests'];
include __DIR__ . '/../general/header.php';
?>
<link rel="stylesheet" href="../../frontend/css/session.css">
<style>
    .request-detail-page {
        max-width: 900px;
        margin: 0 auto;
        padding: 28px 20px 60px;
    }

    .request-detail-card {
        box-sizing: border-box;
        width: 100%;
        padding: 50px 40px;
        border: 1px solid #cfcfcf;
        border-radius: 40px;
        background: #fff;
        box-shadow: 0 3px 5px rgba(0, 0, 0, 0.15);
    }

    .request-section-heading {
        margin: 8px 0 20px;
        color: #222;
        font-size: 24px;
        font-weight: 800;
    }

    .request-detail-row {
        display: grid;
        grid-template-columns: 160px minmax(0, 1fr);
        align-items: center;
        gap: 20px;
        margin: 24px 0;
    }

    .request-detail-label {
        margin: 0;
        color: #333;
        font-size: 16px;
        font-weight: 700;
    }

    .request-detail-value {
        min-width: 0;
        color: #222;
        font-size: 16px;
        font-weight: 500;
        overflow-wrap: anywhere;
    }

    .request-description {
        padding: 14px 16px;
        border: 1px solid #d6d6d6;
        border-radius: 8px;
        background: #f9f9f9;
        line-height: 1.6;
    }

    .request-divider {
        margin: 30px 0;
        border: 0;
        border-top: 1px solid #d9d9d9;
    }

    .request-status {
        display: inline-block;
        padding: 6px 16px;
        border-radius: 20px;
        background: #f7931e;
        color: #fff;
        font-size: 15px;
        font-weight: 600;
    }

    .request-status.is-accepted { background: #7bdc82; }
    .request-status.is-rejected { background: #e53935; }

    .request-detail-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 14px;
        margin-top: 18px;
        padding-top: 24px;
        border-top: 1px solid #d9d9d9;
    }

    .request-detail-actions form { margin: 0; }

    .request-detail-actions .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        min-width: 130px;
        padding: 12px 22px;
        border-radius: 10px;
        font-family: inherit;
        font-size: 15px;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
    }

    .request-detail-actions .btn svg { width: 16px; height: 16px; }
    .request-detail-actions .btn-back { border: 1px solid #ddd; background: #efefef; color: #1f1f1f; }

    @media (max-width: 600px) {
        .request-detail-page { padding: 20px 12px 40px; }
        .request-detail-card { padding: 24px 18px; border-radius: 24px; }
        .request-detail-row { grid-template-columns: 1fr; gap: 10px; margin: 20px 0; }
        .request-detail-actions { justify-content: stretch; }
        .request-detail-actions form, .request-detail-actions > a { flex: 1 1 100%; }
        .request-detail-actions .btn { width: 100%; box-sizing: border-box; }
    }
</style>

<?php echo renderConfirmModal(); ?>

<div class="request-detail-page">
    <div class="request-detail-card">
        <h2 class="request-section-heading">Student Details</h2>
        <div class="request-detail-row">
            <h3 class="request-detail-label">Student Name:</h3>
            <div class="request-detail-value"><?php echo htmlspecialchars($request['student_name']); ?></div>
        </div>
        <div class="request-detail-row">
            <h3 class="request-detail-label">Student Email:</h3>
            <div class="request-detail-value"><?php echo htmlspecialchars($request['student_email']); ?></div>
        </div>

        <hr class="request-divider">

        <h2 class="request-section-heading">Request Details</h2>
        <div class="request-detail-row">
            <h3 class="request-detail-label">Category:</h3>
            <div class="request-detail-value"><?php echo htmlspecialchars($request['category']); ?></div>
        </div>
        <div class="request-detail-row" style="align-items: flex-start;">
            <h3 class="request-detail-label">Description:</h3>
            <div class="request-detail-value request-description"><?php echo nl2br(htmlspecialchars($request['description'])); ?></div>
        </div>
        <div class="request-detail-row">
            <h3 class="request-detail-label">Preferred Day:</h3>
            <div class="request-detail-value"><?php echo htmlspecialchars($request['preferred_day']); ?></div>
        </div>
        <div class="request-detail-row">
            <h3 class="request-detail-label">Preferred Time:</h3>
            <div class="request-detail-value"><?php echo htmlspecialchars(formatTimeRange($request['start_time'], $request['end_time'])); ?></div>
        </div>
        <div class="request-detail-row">
            <h3 class="request-detail-label">Support Mode:</h3>
            <div class="request-detail-value"><?php echo htmlspecialchars($request['support_mode']); ?></div>
        </div>
        <div class="request-detail-row" style="margin-bottom: 0;">
            <h3 class="request-detail-label">Status:</h3>
            <div class="request-detail-value">
                <span class="request-status is-<?php echo strtolower(htmlspecialchars($request['status'])); ?>"><?php echo htmlspecialchars($request['status']); ?></span>
            </div>
        </div>

        <div class="request-detail-actions">
            <?php if ($request['status'] === 'Pending'): ?>
                <form method="post" action="respond_to_request.php">
                    <input type="hidden" name="request_id" value="<?php echo (int) $request['id']; ?>">
                    <input type="hidden" name="response" value="Accepted">
                    <button type="submit" class="btn btn-success"><?php echo cardIcon('complete'); ?>Accept</button>
                </form>
                <form method="post" action="respond_to_request.php">
                    <input type="hidden" name="request_id" value="<?php echo (int) $request['id']; ?>">
                    <input type="hidden" name="response" value="Rejected">
                    <button type="submit" class="btn btn-danger"><?php echo cardIcon('cancel'); ?>Reject</button>
                </form>
            <?php endif; ?>
            <a class="btn btn-back" href="upcoming_request.php"><?php echo cardIcon('back'); ?>Back</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../general/footer.php'; ?>
