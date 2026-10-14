<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/db.php';
require __DIR__ . '/../general/auth.php';
require_once __DIR__ . '/functions.php';

requireLogin();
$user = currentUser();

$requestId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Retrieve full request details joined with assigned volunteer and their profile skills
$stmt = $pdo->prepare("
    SELECT r.*,
           r.skills AS requested_skills,
           us.full_name AS student_name,
           us.email AS student_email,
           uv.full_name AS volunteer_name,
           uv.email AS volunteer_email,
           uv.picture_url AS volunteer_picture,
           v.skills AS volunteer_skills
    FROM requests r
    JOIN users us ON r.user_id = us.id
    LEFT JOIN users uv ON r.volunteer_id = uv.id
    LEFT JOIN volunteers v ON uv.id = v.user_id
    WHERE r.id = ? AND r.user_id = ?
");
$stmt->execute([$requestId, $user['id']]);
$request = $stmt->fetch();

if (!$request) {
    header('Location: request.php');
    exit;
}

include __DIR__ . '/../general/header.php';
?>

<style>
    .page-title {
        width: 100%;
        max-width: 900px;
        margin: 0 auto 28px auto;
        text-align: center;
        font-size: 34px;
        font-weight: 800;
        color: #222222;
    }

    .create-page {
        max-width: 900px;
        margin: 0 auto;
        padding: 28px 20px 60px;
    }

    .form-card {
        background: #ffffff;
        border: 1px solid #cfcfcf;
        border-radius: 40px;
        padding: 50px 40px;
        box-shadow: 0 3px 5px rgba(0, 0, 0, 0.15);
        width: 100%;
        box-sizing: border-box;
    }

    .preference-row {
        display: grid;
        grid-template-columns: 160px minmax(0, 1fr);
        align-items: center;
        gap: 20px;
        margin: 24px 0;
    }

    .preference-row label {
        font-size: 16px;
        font-weight: 700;
        color: #333333;
        margin: 0;
    }

    .preference-control {
        min-width: 0;
        font-size: 16px;
        color: #222222;
        font-weight: 500;
    }

    .status-pill {
        display: inline-block;
        padding: 6px 16px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 15px;
        background: #f7931e;
        color: #ffffff;
    }

    .status-pill.accepted {
        background: #7bdc82;
    }

    .status-pill.rejected {
        background: #e53935;
    }

    .skills-display-container {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        border: 1px solid #d6d6d6;
        border-radius: 8px;
        background: #f9f9f9;
        min-height: 48px;
        box-sizing: border-box;
    }

    .skill-tag {
        display: inline-flex;
        align-items: center;
        background: #f1f3f5;
        border: 1px solid #ced4da;
        border-radius: 6px;
        padding: 5px 12px;
        font-size: 15px;
        font-weight: 600;
        color: #333333;
    }

    .description-box {
        background: #f9f9f9;
        padding: 14px 16px;
        border: 1px solid #d6d6d6;
        border-radius: 8px;
        line-height: 1.6;
        font-size: 16px;
        color: #222222;
    }

    .volunteer-box {
        display: flex;
        align-items: center;
        gap: 16px;
        background: #f9f9f9;
        padding: 16px 20px;
        border-radius: 12px;
        border: 1px solid #e0e0e0;
    }
</style>

<div class="create-page">

    <div class="form-card">

        <div class="preference-row">
            <label>Categories:</label>
            <div class="preference-control">
                <strong><?= htmlspecialchars($request['category']) ?></strong>
            </div>
        </div>

        <div class="preference-row" style="align-items: flex-start;">
            <label style="padding-top: 4px;">Description:</label>
            <div class="preference-control description-box">
                <?= nl2br(htmlspecialchars($request['description'])) ?>
            </div>
        </div>

        <div class="preference-row">
            <label>Preferred Day:</label>
            <div class="preference-control"><?= htmlspecialchars($request['preferred_day']) ?></div>
        </div>

        <div class="preference-row">
            <label>Preferred Time:</label>
            <div class="preference-control">
                <?= htmlspecialchars(date('g:i A', strtotime($request['start_time'])) . ' - ' . date('g:i A', strtotime($request['end_time']))) ?>
            </div>
        </div>

        <div class="preference-row">
            <label>Skills:</label>
            <div class="preference-control">
                <div class="skills-display-container">
                    <?php
                    $skillsList = !empty($request['requested_skills'])
                        ? explode(',', $request['requested_skills'])
                        : [];
                    if (!empty($skillsList) && trim($request['volunteer_skills']) !== ''):
                        foreach ($skillsList as $skill):
                            $trimmed = trim($skill);
                            if ($trimmed !== ''):
                                ?>
                                <span class="skill-tag"><?= htmlspecialchars($trimmed) ?></span>
                                <?php
                            endif;
                        endforeach;
                    else:
                        ?>
                        <span style="color: #888; font-style: italic; padding: 4px;">No specific skills listed.</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="preference-row">
            <label>Support Mode:</label>
            <div class="preference-control"><?= htmlspecialchars($request['support_mode']) ?></div>
        </div>

        <div class="preference-row" style="align-items: flex-start; margin-bottom: 0;">
            <label style="padding-top: 10px;">Volunteer:</label>
            <div class="preference-control">
                <?php if (!empty($request['volunteer_name'])): ?>
                    <div class="volunteer-box">
                        <img src="<?= !empty($request['volunteer_picture']) ? htmlspecialchars($request['volunteer_picture']) : BASE_URL . '/images/default-avatar.png' ?>"
                            alt="Volunteer"
                            style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 1px solid #ccc;">
                        <div>
                            <strong><?= htmlspecialchars($request['volunteer_name']) ?></strong><br>
                            <span
                                style="font-size: 13px; color: #666;"><?= htmlspecialchars($request['volunteer_email']) ?></span>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="padding: 10px 0; color: #888; font-style: italic;">No specific volunteer assigned yet.</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="preference-row">
            <label>Status:</label>
            <div class="preference-control">
                <span class="status-pill <?= strtolower(str_replace(' ', '-', $request['status'])) ?>">
                    <?= htmlspecialchars($request['status']) ?>
                </span>
            </div>
        </div>
    </div>

</div>

<?php include __DIR__ . '/../general/footer.php'; ?>