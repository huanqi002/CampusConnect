<?php
require __DIR__ . '/../general/config.php';
require __DIR__ . '/../general/db.php';     //inside request_management
require __DIR__ . '/../general/auth.php';

requireLogin();

$user = currentUser();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $user['id'];
    $category = $_POST['category'] ?? '';
    $description = $_POST['description'] ?? '';
    $preferredDay = $_POST['preferred_day'] ?? '';
    $preferredTime = $_POST['preferred_time'] ?? '';
    $supportMode = $_POST['support_mode'] ?? '';
    $skills = $_POST['skills'] ?? '';

    $volunteerId = !empty($_POST['selected_volunteer_id']) ? intval($_POST['selected_volunteer_id']) : null;

    if (!empty($category) && !empty($description) && !empty($preferredDay) && !empty($preferredTime) && !empty($supportMode)) {
        // Insert into requests setting notified_at to NOW() so the 7-day response timer starts immediately
        $stmt = $pdo->prepare("
        INSERT INTO requests (
        user_id, volunteer_id, category, description, preferred_day, preferred_time, support_mode, skills, status, notified_at, created_at)            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW(), NOW())");
        $stmt->execute([$userId, $volunteerId, $category, $description, $preferredDay, $preferredTime, $supportMode, $skills]);

        // Automatically create an in-app notification for the student
        $notifStmt = $pdo->prepare("
            INSERT INTO notifications (user_id, message, notice_type, is_read, created_at)
            VALUES (?, ?, 'Request Submitted', 'N', NOW())
        ");
        $notifStmt->execute([$userId, "Your {$category} request has been sent to volunteers. Response deadline timer has started."]);

        header('Location: request.php');
        exit;
    }
}
include __DIR__ . '/../general/header.php';
?>
<style>
    /* Main page */
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

    /* Main white card */
    .form-card {
        background: #ffffff;
        border: 1px solid #cfcfcf;
        border-radius: 40px;
        padding: 50px 30px;
        box-shadow: 0 3px 5px rgba(0, 0, 0, 0.15);
        width: 100%;
        margin-left: 0;
        box-sizing: border-box;
    }

    /* Labels and category */
    label {
        display: block;
        font-weight: 600;
        margin: 14px 0 8px;
        color: #333333;
    }

    .field-row {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .field-row>label {
        margin: 0;
        min-width: 115px;
        flex-shrink: 0;
    }

    .field-row select {
        flex: 1;
        min-width: 0;
        padding: 12px 14px;
        font-size: 15px;
        border: 1px solid #d6d6d6;
        border-radius: 8px;
        font-family: inherit;
        background: #ffffff;
    }

    /* Description */
    .description-group {
        margin-top: 22px;
    }

    .description-group>label {
        margin-top: 0;
    }

    .description-group textarea {
        box-sizing: border-box;
        display: block;
        width: 100%;
        min-height: 100px;
        padding: 12px 14px;
        font-size: 15px;
        border: 1px solid #d6d6d6;
        border-radius: 8px;
        font-family: inherit;
        resize: vertical;
    }

    .field-row select:focus,
    .description-group textarea:focus,
    .time-select:focus,
    .skills-input:focus {
        outline: none;
        border-color: #c62828;
        box-shadow: 0 0 0 3px rgba(198, 40, 40, 0.1);
    }

    /* OK button and validation message */
    .validation-actions {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        margin-top: 10px;
    }

    .ok-btn {
        padding: 9px 28px;
        background: #c62828;
        color: #ffffff;
        border: none;
        border-radius: 8px;
        font-family: inherit;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }

    .ok-btn:hover {
        background: #a91f1f;
    }

    .validation-message {
        margin: 7px 0 0;
        font-size: 13px;
        text-align: right;
        min-height: 16px;
    }

    .validation-message.error {
        color: #c62828;
    }

    .validation-message.success {
        color: #218838;
    }

    /* Section headings */
    .section-heading {
        font-size: 30px;
        font-weight: 700;
        margin: 20px 0 16px;
        color: #222222;
    }

    /* Move Find a Volunteer heading up */
    .section-heading.find-volunteer-heading {
        margin-top: 5px;
    }

    /* Disabled section */
    #matchingFields {
        border: none;
        padding: 0;
        margin: 0;
        min-width: 0;
    }

    #matchingFields:disabled {
        opacity: 0.55;
    }

    #matchingFields:disabled button {
        cursor: not-allowed;
    }

    /* Align preference labels and controls */
    .preference-row {
        display: grid;
        grid-template-columns: 130px minmax(0, 1fr);
        align-items: center;
        gap: 16px;
        margin: 20px 0;
    }

    .preference-row>label {
        margin: 0;
    }

    .preference-control {
        min-width: 0;
    }

    /* Preferred day buttons */
    .day-options {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 8px;
        flex-wrap: wrap;
    }

    .day-btn,
    .mode-btn {
        padding: 8px 15px;
        background: #ffffff;
        color: #333333;
        border: 1px solid #bdbdbd;
        border-radius: 22px;
        font-family: inherit;
        font-size: 14px;
        cursor: pointer;
        transition: background 0.2s, color 0.2s;
    }

    .day-btn:hover,
    .mode-btn:hover {
        border-color: #c62828;
    }

    .day-btn.selected,
    .mode-btn.selected {
        background: #c62828;
        color: #ffffff;
        border-color: #c62828;
    }

    /* Time and skills */
    /* Skill Tag Input Styling */
    .skills-container {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 6px;
        padding: 6px 10px;
        border: 1px solid #d6d6d6;
        border-radius: 8px;
        background: #ffffff;
        min-height: 46px;
        box-sizing: border-box;
    }

    .skills-container:focus-within {
        border-color: #c62828;
        box-shadow: 0 0 0 3px rgba(198, 40, 40, 0.1);
    }

    .skill-tag {
        display: inline-flex;
        align-items: center;
        background: #f1f3f5;
        border: 1px solid #ced4da;
        border-radius: 4px;
        padding: 4px 8px;
        font-size: 14px;
        color: #333;
        gap: 6px;
    }

    .skill-tag .remove-tag {
        cursor: pointer;
        font-weight: bold;
        color: #888;
    }

    .skill-tag .remove-tag:hover {
        color: #c62828;
    }

    .skills-input-field {
        border: none;
        outline: none;
        flex: 1;
        min-width: 140px;
        font-size: 15px;
        font-family: inherit;
        padding: 4px;
        background: transparent;
    }

    /* Support mode pill buttons */
    .radio-group {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    .radio-group label {
        margin: 0;
        font-weight: normal;
    }

    .radio-group input[type="radio"] {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .mode-btn {
        display: inline-block;
        margin: 0;
    }

    .radio-group input[type="radio"]:focus-visible+.mode-btn {
        outline: 2px solid #c62828;
        outline-offset: 3px;
    }

    /* Find button on the right */
    .find-actions {
        display: flex;
        justify-content: flex-end;
        margin-top: 20px;
    }

    .find-btn {
        padding: 10px 28px;
        background: #218838;
        color: #ffffff;
        border: none;
        border-radius: 8px;
        font-family: inherit;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }

    .find-btn:hover {
        background: #19692c;
    }

    /* Volunteer profile box */
    .volunteer-list {
        background: #ffffff;
        border: 1px solid #dddddd;
        border-radius: 12px;
        padding: 18px;
        margin-top: 12px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        min-height: 100px;
    }

    .empty-message {
        text-align: center;
        color: #777777;
        padding: 20px 10px;
        font-size: 14px;
        line-height: 1.6;
    }

    /* Bottom buttons */
    .form-actions {
        display: flex;
        justify-content: flex-end;
        gap: 12px;
        margin-top: 22px;
    }

    .cancel-btn,
    .submit-btn {
        padding: 10px 22px;
        border-radius: 8px;
        font-family: inherit;
        font-size: 14px;
        font-weight: 600;
        text-decoration: none;
    }

    .cancel-btn {
        background: #f2f2f2;
        color: #222222;
        border: 1px solid #cccccc;
    }

    .submit-btn {
        background: #c62828;
        color: #ffffff;
        border: none;
        cursor: pointer;
    }

    .submit-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    @media (max-width: 600px) {
        .create-page {
            padding: 20px 12px 40px;
        }

        .form-card {
            padding: 22px 18px;
        }

        .field-row {
            align-items: stretch;
            flex-direction: column;
            gap: 8px;
        }

        .field-row>label {
            min-width: 0;
        }

        .preference-row {
            grid-template-columns: 1fr;
            gap: 10px;
        }
    }

    .change-volunteer-btn:disabled {
        background: #f0f0f0;
        color: #999999;
        cursor: not-allowed;
    }
</style>

<div class="create-page">

    <h1 class="page-title">Create Support Request</h1>

    <form method="POST" action="request_create.php" id="requestForm">

        <!-- Request details -->
        <div class="form-card">

            <div class="field-row">
                <label for="category">Categories:</label>

                <select name="category" id="category" required>
                    <option value="">-- Select a category --</option>
                    <option value="Academic">Academic</option>
                    <option value="Technology">Technology</option>
                    <option value="New Student">New Student</option>
                    <option value="General Student">General Student</option>
                </select>
            </div>

            <div class="description-group">
                <label for="description">Description:</label>

                <textarea name="description" id="description" maxlength="500" required
                    placeholder="Please describe the support you need..."></textarea>

                <div class="validation-actions">
                    <button type="button" class="ok-btn" id="validateRequest">
                        OK
                    </button>

                    <p id="validationMessage" class="validation-message" role="status" aria-live="polite"></p>
                </div>
            </div>

            <fieldset id="matchingFields" disabled>

                <div class="section-heading find-volunteer-heading">Find a Volunteer</div>

                <!-- Preferred Day -->
                <div class="preference-row">
                    <label>Preferred Day:</label>

                    <div class="day-options">
                        <button type="button" class="day-btn" data-day="Monday">Mon</button>
                        <button type="button" class="day-btn" data-day="Tuesday">Tues</button>
                        <button type="button" class="day-btn" data-day="Wednesday">Wed</button>
                        <button type="button" class="day-btn" data-day="Thursday">Thurs</button>
                        <button type="button" class="day-btn" data-day="Friday">Fri</button>
                        <button type="button" class="day-btn" data-day="Saturday">Sat</button>
                        <button type="button" class="day-btn" data-day="Sunday">Sun</button>
                    </div>
                </div>

                <input type="hidden" name="preferred_day" id="preferred_day" value="">

                <!-- Preferred Time -->
                <div class="preference-row">
                    <label for="preferred_time">Preferred Time:</label>

                    <div class="preference-control">
                        <select name="preferred_time" id="preferred_time" class="time-select" required>
                            <option value="">-- Select a time --</option>

                            <?php for ($h = 0; $h < 24; $h++):
                                $period = $h >= 12 ? 'PM' : 'AM';
                                $hour = $h % 12;

                                if ($hour == 0) {
                                    $hour = 12;
                                }

                                $label = sprintf('%02d:00 %s', $hour, $period);
                                $val = sprintf('%02d:00', $h);
                                ?>
                                <option value="<?= $val ?>">
                                    <?= $label ?>
                                </option>
                            <?php endfor; ?>

                        </select>
                    </div>
                </div>

                <!-- Skills Tag Input -->
                <div class="preference-row">
                    <label for="skills">Skills:</label>

                    <div class="preference-control">
                        <div class="skills-container" id="skillsContainer">
                            <input type="text" id="skillInput" class="skills-input-field"
                                placeholder="Type skill and press Enter">
                        </div>
                        <input type="hidden" name="skills" id="skills" value="">
                    </div>
                </div>

                <!-- Support Mode -->
                <div class="preference-row">
                    <label>Support Mode:</label>

                    <div class="radio-group" id="modeGroup">

                        <label>
                            <input type="radio" name="support_mode" value="Online">
                            <span class="mode-btn">Virtual</span>
                        </label>

                        <label>
                            <input type="radio" name="support_mode" value="Face-to-face">
                            <span class="mode-btn">Face-2-Face</span>
                        </label>

                    </div>
                </div>

                <!-- Find Button -->
                <div class="find-actions">
                    <button type="button" class="find-btn" id="findButton">
                        Find
                    </button>
                </div>

                <!-- Volunteer Profile -->
                <div class="section-heading">Volunteer Profile</div>

                <div class="volunteer-list" id="volunteerList">
                    <p class="empty-message">
                        No volunteers to display yet.
                        <br>
                        Select your preferences and click Find.
                    </p>
                </div>

            </fieldset>

        </div>

        <!-- Bottom actions -->
        <div class="form-actions">
            <a href="request.php" class="cancel-btn">Cancel</a>

            <button type="submit" class="submit-btn" id="submitButton" disabled>
                Submit
            </button>
        </div>

    </form>

</div>

<script>
    const okButton = document.getElementById('validateRequest');
    const category = document.getElementById('category');
    const description = document.getElementById('description');
    const message = document.getElementById('validationMessage');
    const matchingFields = document.getElementById('matchingFields');
    const submitButton = document.getElementById('submitButton');

    const preferredDay = document.getElementById('preferred_day');
    const preferredTime = document.getElementById('preferred_time');
    const skills = document.getElementById('skills');
    const volunteerList = document.getElementById('volunteerList');
    const findButton = document.getElementById('findButton');

    // Display validation messages in red or green.
    function showValidation(text, type) {
        message.textContent = text;
        message.className = 'validation-message ' + type;
    }

    // Reset the search section if the request details change.
    function resetValidation() {
        matchingFields.disabled = true;
        submitButton.disabled = true;

        showValidation('', '');

        volunteerList.innerHTML =
            '<p class="empty-message">' +
            'No volunteers to display yet.<br>' +
            'Select your preferences and click Find.' +
            '</p>';

        document.querySelectorAll('.day-btn').forEach(function (btn) {
            btn.classList.remove('selected');
        });

        preferredDay.value = '';

        document.querySelectorAll('input[name="support_mode"]')
            .forEach(function (radio) {
                radio.checked = false;
            });

        document.querySelectorAll('.mode-btn').forEach(function (btn) {
            btn.classList.remove('selected');
        });

        preferredTime.value = '';
        skills.value = '';
    }

    // Validate category and description when OK is clicked.
    okButton.addEventListener('click', function () {
        matchingFields.disabled = true;
        submitButton.disabled = true;

        if (category.value === '') {
            showValidation('Please select a category.', 'error');
            category.focus();
            return;
        }

        if (description.value.trim() === '') {
            showValidation('Please type some description.', 'error');
            description.focus();
            return;
        }

        matchingFields.disabled = false;

        showValidation(
            'Validation succeeded. Please proceed.',
            'success'
        );
    });

    // If category or description changes, validate again.
    category.addEventListener('change', resetValidation);
    description.addEventListener('input', resetValidation);

    // Select one preferred day.
    document.querySelectorAll('.day-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.day-btn').forEach(function (dayBtn) {
                dayBtn.classList.remove('selected');
            });

            btn.classList.add('selected');
            preferredDay.value = btn.dataset.day;
        });
    });

    // Style the selected support mode.
    document.querySelectorAll('input[name="support_mode"]')
        .forEach(function (radio) {
            radio.addEventListener('change', function () {
                document.querySelectorAll('.mode-btn').forEach(function (btn) {
                    btn.classList.remove('selected');
                });

                const selectedButton = radio.parentElement.querySelector('.mode-btn');

                if (selectedButton) {
                    selectedButton.classList.add('selected');
                }
            });
        });

    // Find button: fetches matching volunteers dynamically via AJAX
    findButton.addEventListener('click', function () {
        if (preferredDay.value === '') {
            volunteerList.innerHTML = '<p class="empty-message">Please select a preferred day.</p>';
            return;
        }

        if (preferredTime.value === '') {
            volunteerList.innerHTML = '<p class="empty-message">Please select a preferred time.</p>';
            return;
        }

        const selectedMode = document.querySelector('input[name="support_mode"]:checked');
        if (!selectedMode) {
            volunteerList.innerHTML = '<p class="empty-message">Please select a support mode.</p>';
            return;
        }

        // Prepare data to send to search_volunteer.php
        const formData = new FormData();
        formData.append('preferred_day', preferredDay.value);
        formData.append('preferred_time', preferredTime.value);
        formData.append('support_mode', selectedMode.value);
        formData.append('skills', skills.value);

        volunteerList.innerHTML = '<p class="empty-message">Searching for matching volunteers...</p>';

        fetch('search_volunteer.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.text())
            .then(html => {
                volunteerList.innerHTML = html;

                // Enable/Disable Submit button based on checkbox selection
                const checkboxes = volunteerList.querySelectorAll('.volunteer-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function () {
                        // Ensure only one checkbox can be selected at a time (radio-like behavior)
                        if (this.checked) {
                            checkboxes.forEach(cb => {
                                if (cb !== this) cb.checked = false;
                            });
                            submitButton.disabled = false;
                        } else {
                            submitButton.disabled = true;
                        }
                    });
                });
            })
            .catch(error => {
                volunteerList.innerHTML = '<p class="empty-message">Error loading volunteers. Please try again.</p>';
            });
    });
    // Skill Tagging Logic
    const skillInput = document.getElementById('skillInput');
    const skillsContainer = document.getElementById('skillsContainer');
    const skillsHiddenInput = document.getElementById('skills');
    let skillsArray = [];

    function updateSkillsHidden() {
        skillsHiddenInput.value = skillsArray.join(',');
    }

    function renderTags() {
        skillsContainer.querySelectorAll('.skill-tag').forEach(tag => tag.remove());

        skillsArray.forEach((skill, index) => {
            const tagEl = document.createElement('span');
            tagEl.className = 'skill-tag';
            tagEl.innerHTML = `${skill} <span class="remove-tag" data-index="${index}">&times;</span>`;
            skillsContainer.insertBefore(tagEl, skillInput);
        });
    }

    skillInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const val = skillInput.value.trim();
            if (val && !skillsArray.includes(val)) {
                skillsArray.push(val);
                renderTags();
                skillInput.value = '';
                updateSkillsHidden();
            }
        }
    });

    skillsContainer.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-tag')) {
            const index = e.target.dataset.index;
            skillsArray.splice(index, 1);
            renderTags();
            updateSkillsHidden();
        }
    });

    // Live Countdown Timer Script
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

<?php include __DIR__ . '/../general/footer.php'; ?>