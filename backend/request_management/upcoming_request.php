<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/auth.php';

requireLogin();

$user = currentUser();
if ($user['role'] !== 'volunteer') {
    header('Location: ../session_management/index.php?err=' . urlencode('Only volunteers can view support requests.'));
    exit;
}

$volunteerId = (int) $user['id'];
$stmt = $conn->prepare("
    SELECT id, category, description, status, created_at, notified_at
    FROM requests
    WHERE volunteer_id = ? AND status = 'Pending'
    ORDER BY created_at DESC, id DESC
");
$stmt->bind_param('i', $volunteerId);
$stmt->execute();
$requests = $stmt->get_result();

include __DIR__ . '/../general/header.php';
?>
<link rel="stylesheet" href="../../frontend/css/session.css">
<style>
    .upcoming-request-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .upcoming-request-header h2 {
        display: inline-block;
        margin: 0;
        padding-bottom: 4px;
        border-bottom: 2px solid #999;
        font-size: 30px;
        font-weight: 700;
        line-height: 1;
    }

    .upcoming-request-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }

    .upcoming-request-card {
        position: relative;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        width: 100%;
        padding: 20px 22px;
        border: 1px solid #cfcfcf;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 3px 5px rgba(0, 0, 0, 0.15);
        cursor: pointer;
    }

    .upcoming-request-card:hover {
        border-color: #999;
    }

    .upcoming-request-details {
        flex: 1;
        min-width: 0;
    }

    .upcoming-request-details p {
        margin: 4px 0;
        font-size: 15px;
        line-height: 1.5;
        overflow-wrap: anywhere;
    }

    .upcoming-request-status {
        flex-shrink: 0;
        width: 140px;
        margin: 8px 0 0 18px;
        padding: 9px 0;
        border-radius: 12px;
        background: #f7931e;
        color: #fff;
        text-align: center;
        font-size: 15px;
        font-weight: 600;
    }

    @media (max-width: 600px) {
        .upcoming-request-card {
            gap: 8px;
        }

        .upcoming-request-status {
            width: auto;
            min-width: 90px;
            margin-left: 4px;
            padding: 7px 10px;
        }
    }
</style>

<div class="request-page">
    <?php if (isset($_GET['err'])): ?>
        <div class="message message-error"><?= htmlspecialchars($_GET['err']) ?></div>
    <?php endif; ?>

    <div class="upcoming-request-list">
        <?php if ($requests->num_rows > 0): ?>
            <?php while ($request = $requests->fetch_assoc()):
                $baseTime = !empty($request['notified_at']) ? $request['notified_at'] : $request['created_at'];
                $deadlineTs = !empty($baseTime) ? strtotime($baseTime) + (7 * 24 * 3600) : null;
                $deadlineMs = $deadlineTs ? $deadlineTs * 1000 : 0;
                ?>
                <div class="upcoming-request-card"
                    onclick="window.location.href='upcoming_request_details.php?id=<?= (int) $request['id'] ?>'">
                    <div class="upcoming-request-details">
                        <p><strong>Category:</strong> <?= htmlspecialchars($request['category']) ?></p>
                        <p><strong>Description:</strong> <?= htmlspecialchars($request['description']) ?></p>
                        <p><strong>Response Deadline:</strong>
                            <?php if ($deadlineTs !== null): ?>
                                <span class="request-countdown" data-deadline="<?= $deadlineMs ?>">Calculating timer...</span>
                            <?php else: ?>
                                Not available
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="upcoming-request-status"><?= htmlspecialchars($request['status']) ?></div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="message message-success">No requests have been assigned to you yet.</div>
        <?php endif; ?>
    </div>
</div>

<script>
    function updateRequestCountdowns() {
        document.querySelectorAll('.request-countdown').forEach(function (timer) {
            const deadline = Number(timer.dataset.deadline);
            const distance = deadline - Date.now();

            if (distance <= 0) {
                timer.textContent = 'Deadline Expired';
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            timer.textContent = `${days}d ${hours}h ${minutes}m ${seconds}s remaining`;
        });
    }

    updateRequestCountdowns();
    setInterval(updateRequestCountdowns, 1000);
</script>

<?php
$stmt->close();
include __DIR__ . '/../general/footer.php';
?>