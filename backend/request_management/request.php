<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';
require __DIR__ . '/../session_management/result_modal.php';
requireLogin();
$user = currentUser();
$userId = $user['id'];

$sql = "
    SELECT id, category, description, status, created_at, notified_at, responded_at
    FROM requests
    WHERE user_id = ?
    ORDER BY created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$done = $_GET['done'] ?? '';
$resultModal = $done === 'request_created'
    ? renderResultModal('check', 'Request Submitted', 'Your support request has been sent to volunteers. The response deadline has started.', 'success')
    : '';

include __DIR__ . '/../general/header.php';
?>
<link rel="stylesheet" href="../../frontend/css/session.css">
<style>
    .page-title {
        text-align: center;
        font-size: 36px;
        font-weight: 800;
        margin: 0 0 20px;
    }

    .request-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .request-header h2 {
        display: inline-block;
        font-size: 30px;
        font-weight: 700;
        margin: 0;
        padding-bottom: 4px;
        border-bottom: 2px solid #999999;
        line-height: 1;
    }

    .request-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .request-card {
        position: relative;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        width: 100%;
        background: #ffffff;
        border: 1px solid #cfcfcf;
        border-radius: 12px;
        padding: 20px 22px;
        box-shadow: 0 3px 5px rgba(0, 0, 0, 0.15);
        cursor: pointer;
    }

    .request-card:hover {
        border-color: #999;
    }

    .request-details {
        flex: 1;
        min-width: 0;
    }

    .request-details p {
        margin: 4px 0;
        font-size: 15px;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    a.create-request-btn,
    .create-request-btn {
        display: inline-flex;
        align-items: center;
        padding: 8px 16px;
        background: #eeeeee;
        color: #222222;
        border: 1px solid #888888;
        border-radius: 10px;
        font-family: inherit;
        font-size: 15px;
        text-decoration: none;
        cursor: pointer;
    }

    a.create-request-btn:hover,
    .create-request-btn:hover {
        background: #e0e0e0;
    }

    .request-status {
        flex-shrink: 0;
        width: 140px;
        padding: 9px 0;
        border-radius: 12px;
        text-align: center;
        font-size: 15px;
        font-weight: 600;
        margin-left: 18px;
        margin-top: 8px;
    }

    .request-status.pending {
        background: #f7931e;
        color: #ffffff;
    }

    .request-status.accepted {
        background: #7bdc82;
        color: #ffffff;
    }

    .request-status.rejected {
        background: #e53935;
        color: #ffffff;
    }

    .change-volunteer-btn {
        margin-top: 5px;
        padding: 4px 16px;
        background: #d3d3d3;
        color: #555555;
        border: none;
        border-radius: 12px;
        font-family: inherit;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
    }

    .change-volunteer-btn:hover:not(:disabled) {
        background: #c62828;
        color: #ffffff;
    }

    .change-volunteer-btn:disabled {
        background: #f0f0f0;
        color: #999999;
        cursor: not-allowed;
    }
</style>
<div class="request-page">
    <div class="request-header">
        <h2>Submitted</h2>
        <a href="request_create.php" class="create-request-btn">+ Create</a>
    </div>

    <div class="request-list">
        <?php echo $resultModal; ?>

        <?php if ($result->num_rows > 0): ?>
            <?php while ($request = $result->fetch_assoc()): ?>
                <?php
                // Fallback to created_at if notified_at is empty, then add 7 days
                $baseTime = !empty($request['notified_at']) ? $request['notified_at'] : $request['created_at'];
                $deadlineTs = !empty($baseTime) ? strtotime($baseTime) + (7 * 24 * 3600) : null;
                $deadlineMs = $deadlineTs ? $deadlineTs * 1000 : 0;
                $requestStatus = strtolower($request['status']);
                $isPending = $requestStatus === 'pending';
                $isAccepted = $requestStatus === 'accepted';
                $isRejected = $requestStatus === 'rejected';

                // Define $canChange so the button knows its initial state
                $deadlineOver = ($deadlineTs !== null) && $deadlineTs <= time();
                $canChange = $isRejected || ($deadlineOver && !$isAccepted);
                ?>
                <div class="request-card" onclick="window.location.href='request_review.php?id=<?= $request['id'] ?>'">
                    <div class="request-details">
                        <p><strong>Category:</strong> <?= htmlspecialchars($request['category']) ?></p>
                        <p><strong>Description:</strong> <?= htmlspecialchars($request['description']) ?></p>

                        <p><strong><?= $isPending ? 'Response Deadline:' : 'Note:' ?></strong>
                            <?php if ($isPending && $deadlineTs !== null): ?>
                                <span class="countdown-timer" data-deadline="<?= $deadlineMs ?>">
                                    Calculating timer...
                                </span>
                            <?php elseif ($requestStatus === 'accepted'): ?>
                                Volunteer has accepted this request.
                            <?php elseif ($requestStatus === 'rejected'): ?>
                                Volunteer has rejected this request. Please click Change Volunteer to find another volunteer.
                            <?php else: ?>
                                <?= htmlspecialchars(ucfirst($requestStatus)) ?>
                            <?php endif; ?>
                        </p>

                        <button type="button" class="change-volunteer-btn" <?= $canChange ? '' : 'disabled' ?>
                            onclick="event.stopPropagation(); window.location.href='request_create.php?change_id=<?= $request['id'] ?>'">
                            Change Volunteer
                        </button>
                    </div>
                    <div class="request-status <?= strtolower(str_replace(' ', '-', $request['status'])) ?>">
                        <?= htmlspecialchars($request['status']) ?>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="message message-success">Nothing Created</div>
        <?php endif; ?>
    </div>
</div>

<script>
    function updateCountdowns() {
        const timers = document.querySelectorAll('.countdown-timer');
        timers.forEach(timer => {
            const deadlineMs = parseInt(timer.dataset.deadline);
            const isAccepted = timer.dataset.accepted === 'true';
            const card = timer.closest('.request-card');
            const changeBtn = card.querySelector('.change-volunteer-btn');
            const now = new Date().getTime();
            const distance = deadlineMs - now;

            if (distance < 0) {
                timer.textContent = "Deadline Expired";
                if (!isAccepted) {
                    changeBtn.disabled = false;
                }
            } else {
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                timer.textContent = `${days}d ${hours}h ${minutes}m ${seconds}s remaining`;
            }
        });
    }

    setInterval(updateCountdowns, 1000);
    updateCountdowns();
</script>

<?php
$stmt->close();
include __DIR__ . '/../general/footer.php';
?>