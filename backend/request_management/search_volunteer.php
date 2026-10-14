<?php
require_once __DIR__ . '/../general/config.php';
require_once __DIR__ . '/../general/db.php';
require_once __DIR__ . '/../general/auth.php';
require_once __DIR__ . '/functions.php';

requireLogin();

$currentUserId = currentUser()['id'];
$day = $_POST['preferred_day'] ?? '';
$startTime = $_POST['start_time'] ?? '';
$endTime = $_POST['end_time'] ?? '';
$mode = $_POST['support_mode'] ?? '';
$skills = $_POST['skills'] ?? '';

$volunteers = findMatchingVolunteers($pdo, $day, $startTime, $endTime, $mode, $skills, $currentUserId);

if (empty($volunteers)) {
    echo '<p class="empty-message">No volunteers found matching your exact criteria in the database.</p>';
    exit;
}

foreach ($volunteers as $volunteer) {
    $id = htmlspecialchars($volunteer['user_id'] ?? '', ENT_QUOTES, 'UTF-8');
    $name = htmlspecialchars($volunteer['full_name'] ?? 'Volunteer', ENT_QUOTES, 'UTF-8');
    $vSkills = htmlspecialchars($volunteer['skills'] ?? 'General Support', ENT_QUOTES, 'UTF-8');
    $vDay = htmlspecialchars($volunteer['preferred_day'] ?? $day, ENT_QUOTES, 'UTF-8');
    $vStartTime = htmlspecialchars($volunteer['start_time'] ?? $startTime, ENT_QUOTES, 'UTF-8');
    $vEndTime = htmlspecialchars($volunteer['end_time'] ?? $endTime, ENT_QUOTES, 'UTF-8');
    $vMode = htmlspecialchars($volunteer['support_mode'] ?? $mode, ENT_QUOTES, 'UTF-8');
    $picture = !empty($volunteer['picture_url'])
        ? htmlspecialchars($volunteer['picture_url'], ENT_QUOTES, 'UTF-8')
        : BASE_URL . '/images/default-avatar.png';
    echo '
    <div class="volunteer-card-item" style="display: flex; align-items: center; justify-content: space-between; background: #fff; border: 1px solid #ddd; border-radius: 12px; padding: 12px 16px; margin-bottom: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">

        <!-- Left: Profile Picture -->
        <div style="display: flex; align-items: center; gap: 14px; flex: 1;">
            <img src="' . $picture . '" alt="Profile" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 1px solid #ccc;">

            <!-- Middle: Dropdown Details List -->
            <details style="width: 100%; cursor: pointer;">
                <summary style="font-weight: 600; font-size: 16px; color: #222; outline: none;">
                    ' . $name . ' <span style="font-weight: normal; font-size: 13px; color: #666; margin-left: 8px;">(Click to view details)</span>
                </summary>
                <div style="margin-top: 8px; padding: 10px; background: #f9f9f9; border-radius: 8px; font-size: 14px; color: #444;">
                    <div><strong>Skills:</strong> ' . $vSkills . '</div>
                    <div><strong>Preferred Day:</strong> ' . $vDay . '</div>
                    <div><strong>Preferred Time:</strong> ' . $vStartTime . ' - ' . $vEndTime . '</div>
                    <div><strong>Support Mode:</strong> ' . $vMode . '</div>
                </div>
            </details>
        </div>

        <!-- Right: Checkbox Selection -->
        <div style="padding-left: 15px;">
            <input type="checkbox" name="selected_volunteer_id" class="volunteer-checkbox" value="' . $id . '" style="width: 20px; height: 20px; cursor: pointer;">
        </div>

    </div>';

    // Read incoming parameters
    $preferredDay = $_POST['preferred_day'] ?? '';
    $startTime = $_POST['start_time'] ?? '';
    $endTime = $_POST['end_time'] ?? '';
    $supportMode = $_POST['support_mode'] ?? '';
    $skillsInput = $_POST['skills'] ?? '';

    // Base query joining volunteers with users
    $sql = "
    SELECT v.*, u.full_name, u.email, u.picture_url
    FROM volunteers v
    JOIN users u ON v.user_id = u.id
    WHERE v.preferred_day = ? AND v.start_time = ? AND v.end_time = ? AND v.support_mode = ?";

    $params = [$preferredDay, $startTime, $endTime, $supportMode];

    // Split the comma-separated skills into an array of individual skills
    if (!empty(trim($skillsInput))) {
        $skillsArray = array_map('trim', explode(',', $skillsInput));

        foreach ($skillsArray as $singleSkill) {
            if ($singleSkill !== '') {
                // Search for EACH individual skill using wildcard matching
                $sql .= " AND v.skills LIKE ?";
                $params[] = '%' . $singleSkill . '%';
            }
        }
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $volunteers = $stmt->fetchAll();
}
?>