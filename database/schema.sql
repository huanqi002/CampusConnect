CREATE DATABASE IF NOT EXISTS CampusConnect
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE CampusConnect;

DROP TABLE IF EXISTS calendar_tokens;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS history;
DROP TABLE IF EXISTS feedbacks;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS requests;
DROP TABLE IF EXISTS volunteers;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id                        INT AUTO_INCREMENT PRIMARY KEY,
    student_id                VARCHAR(30)   NOT NULL,
    email                     VARCHAR(150)  NOT NULL,
    personalemail             VARCHAR(150)  NULL,
    full_name                 VARCHAR(100)  NOT NULL,
    university_name           VARCHAR(100)  NOT NULL,
    password_hash             VARCHAR(255)  NOT NULL,
    picture_url               VARCHAR(255)  NULL,
    cover_photo_url           VARCHAR(255)  NULL,
    volunteer_picture_url     VARCHAR(255)  NULL,
    volunteer_cover_photo_url VARCHAR(255)  NULL,
    education                 VARCHAR(500)  NULL,
    skills                    VARCHAR(500)  NULL,
    support_experience        VARCHAR(1000) NULL,
    tfa_code                  VARCHAR(255)  NULL,
    tfa_expiry                DATETIME      NULL,
    failed_attempts           INT           NOT NULL DEFAULT 0,
    locked_until              DATETIME      NULL,
    remember_token_hash       CHAR(64)      NULL,
    remember_expires          DATETIME      NULL,
    reset_code                VARCHAR(6)    NULL,
    reset_expiry              DATETIME      NULL,
    is_volunteer              CHAR(1)       NOT NULL DEFAULT 'N',
    is_active                 CHAR(1)       NOT NULL DEFAULT 'N',
    created_at                DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_users_email      UNIQUE (email),
    CONSTRAINT uq_users_student_id UNIQUE (student_id),
    CONSTRAINT chk_users_volunteer CHECK (is_volunteer IN ('Y', 'N')),
    CONSTRAINT chk_users_active    CHECK (is_active IN ('Y', 'N'))
);

CREATE TABLE volunteers (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT         NOT NULL, 
    category       VARCHAR(50) NOT NULL,
    preferred_day  VARCHAR(10) NOT NULL,
    start_time     TIME        NOT NULL,
    end_time       TIME        NOT NULL,
    skills         VARCHAR(255) NULL,
    support_mode   VARCHAR(20) NOT NULL,
    is_available   CHAR(1)     NOT NULL DEFAULT 'N',
    created_at     DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_volunteers_slot  UNIQUE (user_id, preferred_day, start_time, end_time),
    CONSTRAINT chk_volunteers_day  CHECK (preferred_day IN ('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')),
    CONSTRAINT chk_volunteers_mode CHECK (support_mode IN ('Online', 'Face-to-face')),
    CONSTRAINT chk_volunteers_flag CHECK (is_available IN ('Y', 'N')),
    CONSTRAINT chk_volunteers_time CHECK (end_time > start_time),
    CONSTRAINT fk_volunteers_user  FOREIGN KEY (user_id) REFERENCES users (id)
);

CREATE TABLE requests (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT          NOT NULL,
    volunteer_id   INT          NULL,
    category       VARCHAR(50)  NOT NULL,
    description    VARCHAR(500) NOT NULL,
    preferred_day  VARCHAR(10)  NOT NULL,
    start_time     TIME         NOT NULL,
    end_time       TIME         NOT NULL,
    skills         VARCHAR(255) NULL,
    support_mode   VARCHAR(20)  NOT NULL,
    status         VARCHAR(20)  NOT NULL DEFAULT 'Pending',
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    notified_at    DATETIME     NULL,
    responded_at   DATETIME     NULL,     
    INDEX idx_requests_status (status),
    CONSTRAINT chk_requests_day      CHECK (preferred_day IN ('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')),
    CONSTRAINT chk_requests_mode     CHECK (support_mode IN ('Online', 'Face-to-face')),
    CONSTRAINT chk_requests_time     CHECK (end_time > start_time),
    CONSTRAINT fk_requests_user      FOREIGN KEY (user_id)      REFERENCES users (id),
    CONSTRAINT fk_requests_volunteer FOREIGN KEY (volunteer_id) REFERENCES users (id)
);

CREATE TABLE sessions (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    request_id     INT          NOT NULL,
    user_id        INT          NOT NULL, 
    volunteer_id   INT          NOT NULL,
    category       VARCHAR(50)  NOT NULL,
    description    VARCHAR(500) NOT NULL,
    session_date   DATE         NULL,     
    start_time     TIME         NULL,     
    end_time       TIME         NULL,
    support_mode   VARCHAR(20)  NOT NULL,
    status         VARCHAR(20)  NOT NULL DEFAULT 'Unscheduled',
    conf_student   CHAR(1)      NOT NULL DEFAULT 'N',
    conf_volunteer CHAR(1)      NOT NULL DEFAULT 'N',  
    created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT chk_sessions_conf_student   CHECK (conf_student IN ('Y', 'N')),
    CONSTRAINT chk_sessions_conf_volunteer CHECK (conf_volunteer IN ('Y', 'N')),
    CONSTRAINT chk_sessions_times          CHECK (end_time IS NULL OR end_time > start_time),
    CONSTRAINT chk_sessions_booked         CHECK (status IN ('Unscheduled', 'Cancelled') OR (session_date IS NOT NULL AND start_time IS NOT NULL)),
    CONSTRAINT chk_sessions_mode           CHECK (support_mode IN ('Online', 'Face-to-face')),
    CONSTRAINT fk_sessions_request   FOREIGN KEY (request_id)   REFERENCES requests (id),
    CONSTRAINT fk_sessions_user      FOREIGN KEY (user_id)      REFERENCES users (id),
    CONSTRAINT fk_sessions_volunteer FOREIGN KEY (volunteer_id) REFERENCES users (id)
);

CREATE TABLE feedbacks (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    session_id   INT          NOT NULL,
    user_id      INT          NOT NULL, 
    volunteer_id INT          NOT NULL, 
    rating       TINYINT      NOT NULL, 
    comments     VARCHAR(500) NULL,
    submitted_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT uq_feedbacks_session   UNIQUE (session_id),
    CONSTRAINT chk_feedbacks_rating   CHECK (rating BETWEEN 1 AND 5),
    CONSTRAINT fk_feedbacks_session   FOREIGN KEY (session_id)   REFERENCES sessions (id),
    CONSTRAINT fk_feedbacks_user      FOREIGN KEY (user_id)      REFERENCES users (id),
    CONSTRAINT fk_feedbacks_volunteer FOREIGN KEY (volunteer_id) REFERENCES users (id)
);

CREATE TABLE history (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    session_id          INT          NOT NULL,
    user_id             INT          NOT NULL,
    volunteer_id        INT          NOT NULL,
    feedback_id         INT          NULL,
    final_status        VARCHAR(20)  NOT NULL,
    completion_time     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    cancellation_reason VARCHAR(500) NULL,
    CONSTRAINT uq_history_session   UNIQUE (session_id),
    CONSTRAINT fk_history_session   FOREIGN KEY (session_id)   REFERENCES sessions (id),
    CONSTRAINT fk_history_user      FOREIGN KEY (user_id)      REFERENCES users (id),
    CONSTRAINT fk_history_volunteer FOREIGN KEY (volunteer_id) REFERENCES users (id),
    CONSTRAINT fk_history_feedback  FOREIGN KEY (feedback_id)  REFERENCES feedbacks (id)
);

CREATE TABLE notifications (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          NOT NULL,
    message     VARCHAR(500) NOT NULL,
    notice_type VARCHAR(50)  NOT NULL,
    is_read     CHAR(1)      NOT NULL DEFAULT 'N',
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_unread (user_id, is_read),
    CONSTRAINT chk_notifications_read CHECK (is_read IN ('Y', 'N')),
    CONSTRAINT fk_notifications_user  FOREIGN KEY (user_id) REFERENCES users (id)
);

-- Google Calendar: the organizer account (organizer_user_id in general/google_config.php) connects once and its
-- refresh token is stored here. Tokens come from the OAuth sign-in on each machine, so none are seeded.
CREATE TABLE calendar_tokens (
    user_id       INT PRIMARY KEY,
    refresh_token TEXT NOT NULL,
    connected_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_calendar_tokens_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- personalemail is where calendar invitations are sent (falls back to email when empty).
INSERT INTO users (id, student_id, email, personalemail, full_name, university_name, password_hash, education, skills, support_experience, is_volunteer, is_active, created_at) VALUES
(1, 'B123',     'b123@helplive.edu.my',     'yukielian06@hotmail.com',    'Yukie Lian',  'HELP University', '$2y$10$1Y1GcuDG6D2ofr20THDxNeLmOlsAMDeAjHWRcrbBR6kAvf.2kan6W', 'Bachelor of Psychology',          'Mathematics, Statistics, Academic writing', 'Peer tutor for first-year students.',     'Y', 'Y', '2026-10-14 14:54:22'),
(2, 'B2300554', 'b2300554@helplive.edu.my', 'haileyhuanqi@gmail.com',    'Low Huan Qi', 'HELP University', '$2y$10$5zYWMviLaGOgYJb7a7fLpuCqN5AEyaQlzvwi9z1ZjnWpL5aj7Izz.', 'Bachelor of Computer Science',    'PHP, MySQL, JavaScript, CSS',               'Helped classmates debug web projects.',   'Y', 'Y', '2026-10-14 14:58:00'),
(3, 'B2300003', 'melvin@helplive.edu.my',   'melvinczl666@gmail.com',     'Melvin',      'HELP University', '$2y$10$ilRV9CW9s.7DbKu7c6lwTOcsUNW70NiCn3qzyp5A8fAUlstcrX9ei', 'Bachelor of Business',            'Time management, Study planning, Presenting', 'Student mentor in the orientation program.', 'Y', 'Y', '2026-10-14 15:00:00');

-- Volunteers: preferred day with a start and end time ----------------------
INSERT INTO volunteers (user_id, category, preferred_day, start_time, end_time, skills, support_mode, is_available) VALUES
(1, 'Academic',        'Monday',    '09:00:00', '12:00:00', 'Mathematics, Statistics, Academic writing',   'Online',       'Y'),
(1, 'Academic',        'Wednesday', '13:00:00', '16:00:00', 'Mathematics, Statistics, Academic writing',   'Online',       'Y'),
(1, 'Academic',        'Friday',    '10:00:00', '13:00:00', 'Mathematics, Statistics, Academic writing',   'Face-to-face', 'Y'),
(1, 'Academic',        'Saturday',  '09:00:00', '12:00:00', 'Mathematics, Statistics, Academic writing',   'Online',       'Y'),
(2, 'Technology',      'Monday',    '10:00:00', '13:00:00', 'PHP, MySQL, JavaScript, CSS',                 'Online',       'Y'),
(2, 'Technology',      'Tuesday',   '14:00:00', '17:00:00', 'PHP, MySQL, JavaScript, CSS',                 'Online',       'Y'),
(2, 'Technology',      'Wednesday', '09:00:00', '12:00:00', 'PHP, MySQL, JavaScript, CSS',                 'Online',       'Y'),
(2, 'Technology',      'Thursday',  '13:00:00', '16:00:00', 'PHP, MySQL, JavaScript, CSS',                 'Face-to-face', 'Y'),
(2, 'Technology',      'Friday',    '10:00:00', '13:00:00', 'PHP, MySQL, JavaScript, CSS',                 'Online',       'Y'),
(2, 'Technology',      'Saturday',  '09:00:00', '12:00:00', 'PHP, MySQL, JavaScript, CSS',                 'Online',       'Y'),
(3, 'General Student', 'Tuesday',   '09:00:00', '12:00:00', 'Time management, Study planning, Presenting', 'Online',       'Y'),
(3, 'General Student', 'Thursday',  '10:00:00', '13:00:00', 'Time management, Study planning, Presenting', 'Online',       'Y'),
(3, 'New Student',     'Saturday',  '13:00:00', '16:00:00', 'Campus orientation, Course registration',     'Face-to-face', 'Y'),
(3, 'General Student', 'Sunday',    '14:00:00', '17:00:00', 'Time management, Study planning, Presenting', 'Online',       'N');

-- Requests: 1-21 accepted (one per session), 22-24 still waiting for a volunteer ----------------
INSERT INTO requests (id, user_id, volunteer_id, category, description, preferred_day, start_time, end_time, skills, support_mode, status, created_at, notified_at, responded_at) VALUES
(1,  1, 2,    'Technology',      'Fixing CSS layout issues on my website',       'Monday',    '10:00:00', '13:00:00', 'CSS',              'Online',       'Accepted', NOW() - INTERVAL 6 DAY,   NOW() - INTERVAL 6 DAY,   NOW() - INTERVAL 5 DAY),
(2,  3, 2,    'Technology',      'Learning Git basics for group work',           'Tuesday',   '14:00:00', '17:00:00', 'Git',              'Face-to-face', 'Accepted', NOW() - INTERVAL 6 DAY,   NOW() - INTERVAL 6 DAY,   NOW() - INTERVAL 5 DAY),
(3,  1, 2,    'Technology',      'Connecting PHP to a MySQL database',           'Wednesday', '09:00:00', '12:00:00', 'PHP, MySQL',       'Online',       'Accepted', NOW() - INTERVAL 7 DAY,   NOW() - INTERVAL 7 DAY,   NOW() - INTERVAL 6 DAY),
(4,  3, 2,    'Technology',      'Understanding JavaScript form validation',     'Thursday',  '13:00:00', '16:00:00', 'JavaScript',       'Online',       'Accepted', NOW() - INTERVAL 8 DAY,   NOW() - INTERVAL 8 DAY,   NOW() - INTERVAL 7 DAY),
(5,  1, 2,    'Technology',      'Making my portfolio site mobile friendly',     'Friday',    '10:00:00', '13:00:00', 'CSS',              'Face-to-face', 'Accepted', NOW() - INTERVAL 5 DAY,   NOW() - INTERVAL 5 DAY,   NOW() - INTERVAL 4 DAY),
(6,  3, 2,    'Technology',      'Help with Python loops and functions',         'Monday',    '10:00:00', '13:00:00', 'Python',           'Online',       'Accepted', NOW() - INTERVAL 3 DAY,   NOW() - INTERVAL 3 DAY,   NOW() - INTERVAL 2 DAY),
(7,  1, 2,    'Technology',      'Introduction to SQL joins',                    'Tuesday',   '14:00:00', '17:00:00', 'MySQL',            'Online',       'Accepted', NOW() - INTERVAL 13 DAY,  NOW() - INTERVAL 13 DAY,  NOW() - INTERVAL 12 DAY),
(8,  3, 2,    'Technology',      'Setting up VS Code for web development',       'Wednesday', '09:00:00', '12:00:00', NULL,               'Online',       'Accepted', NOW() - INTERVAL 10 DAY,  NOW() - INTERVAL 10 DAY,  NOW() - INTERVAL 9 DAY),
(9,  2, 1,    'Academic',        'Statistics: hypothesis testing basics',        'Wednesday', '13:00:00', '16:00:00', 'Statistics',       'Online',       'Accepted', NOW() - INTERVAL 5 DAY,   NOW() - INTERVAL 5 DAY,   NOW() - INTERVAL 4 DAY),
(10, 3, 1,    'Academic',        'Essay structure for my English assignment',    'Friday',    '10:00:00', '13:00:00', 'Academic writing', 'Online',       'Accepted', NOW() - INTERVAL 6 DAY,   NOW() - INTERVAL 6 DAY,   NOW() - INTERVAL 5 DAY),
(11, 2, 1,    'Academic',        'Algebra practice before my quiz',              'Monday',    '09:00:00', '12:00:00', 'Mathematics',      'Online',       'Accepted', NOW() - INTERVAL 8 DAY,   NOW() - INTERVAL 8 DAY,   NOW() - INTERVAL 7 DAY),
(12, 3, 1,    'Academic',        'Probability practice questions',               'Saturday',  '09:00:00', '12:00:00', 'Statistics',       'Online',       'Accepted', NOW() - INTERVAL 2 DAY,   NOW() - INTERVAL 2 DAY,   NOW() - INTERVAL 1 DAY),
(13, 2, 1,    'Academic',        'Help with Calculus - differentiation',         'Wednesday', '13:00:00', '16:00:00', 'Mathematics',      'Online',       'Accepted', NOW() - INTERVAL 10 DAY,  NOW() - INTERVAL 10 DAY,  NOW() - INTERVAL 9 DAY),
(14, 3, 1,    'Academic',        'Reviewing my lab report',                      'Friday',    '10:00:00', '13:00:00', NULL,               'Online',       'Accepted', NOW() - INTERVAL 5 DAY,   NOW() - INTERVAL 5 DAY,   NOW() - INTERVAL 4 DAY),
(15, 1, 3,    'General Student', 'Planning my revision timetable',               'Tuesday',   '09:00:00', '12:00:00', NULL,               'Online',       'Accepted', NOW() - INTERVAL 3 DAY,   NOW() - INTERVAL 3 DAY,   NOW() - INTERVAL 2 DAY),
(16, 2, 3,    'General Student', 'Tips for presenting in front of a class',      'Thursday',  '10:00:00', '13:00:00', 'Presenting',       'Online',       'Accepted', NOW() - INTERVAL 5 DAY,   NOW() - INTERVAL 5 DAY,   NOW() - INTERVAL 4 DAY),
(17, 1, 3,    'General Student', 'Managing deadlines across four subjects',      'Tuesday',   '09:00:00', '12:00:00', NULL,               'Online',       'Accepted', NOW() - INTERVAL 2 DAY,   NOW() - INTERVAL 2 DAY,   NOW() - INTERVAL 1 DAY),
(18, 2, 3,    'New Student',     'Finding the library and printing services',    'Saturday',  '13:00:00', '16:00:00', NULL,               'Face-to-face', 'Accepted', NOW() - INTERVAL 2 DAY,   NOW() - INTERVAL 2 DAY,   NOW() - INTERVAL 1 DAY),
(19, 1, 3,    'General Student', 'Advice on managing my study timetable',        'Thursday',  '10:00:00', '13:00:00', NULL,               'Online',       'Accepted', NOW() - INTERVAL 14 DAY,  NOW() - INTERVAL 14 DAY,  NOW() - INTERVAL 13 DAY),
(20, 2, 3,    'General Student', 'Preparing slides for my presentation',         'Tuesday',   '09:00:00', '12:00:00', 'Presenting',       'Online',       'Accepted', NOW() - INTERVAL 17 DAY,  NOW() - INTERVAL 17 DAY,  NOW() - INTERVAL 16 DAY),
(21, 1, 3,    'New Student',     'Registering for next semester''s courses',     'Saturday',  '13:00:00', '16:00:00', NULL,               'Face-to-face', 'Accepted', NOW() - INTERVAL 6 DAY,   NOW() - INTERVAL 6 DAY,   NOW() - INTERVAL 5 DAY),
(22, 1, NULL, 'Technology',      'Help installing XAMPP on my laptop',           'Friday',    '17:00:00', '19:00:00', 'PHP',              'Online',       'Pending',  NOW() - INTERVAL 1 DAY,   NOW() - INTERVAL 1 DAY,   NULL),
(23, 2, NULL, 'Academic',        'Linear algebra: matrices and vectors',         'Thursday',  '14:00:00', '16:00:00', 'Mathematics',      'Online',       'Pending',  NOW() - INTERVAL 12 HOUR, NOW() - INTERVAL 12 HOUR, NULL),
(24, 3, NULL, 'Technology',      'Designing a database for my project',          'Wednesday', '09:00:00', '11:00:00', 'MySQL',            'Face-to-face', 'Pending',  NOW() - INTERVAL 3 HOUR,  NOW() - INTERVAL 3 HOUR,  NULL);

-- Sessions in every status --------------------------------------------------
INSERT INTO sessions (id, request_id, user_id, volunteer_id, category, description, session_date, start_time, end_time, support_mode, status, conf_student, conf_volunteer, created_at) VALUES
-- Low Huan Qi (2) as VOLUNTEER
(1,  1,  1, 2, 'Technology',      'Fixing CSS layout issues on my website',     CURDATE() + INTERVAL 2 DAY,  '10:00:00', '11:00:00', 'Online',       'Pending',     'Y', 'N', NOW() - INTERVAL 5 DAY),
(2,  2,  3, 2, 'Technology',      'Learning Git basics for group work',         CURDATE() + INTERVAL 5 DAY,  '14:00:00', '15:30:00', 'Face-to-face', 'Pending',     'Y', 'N', NOW() - INTERVAL 5 DAY),
(3,  3,  1, 2, 'Technology',      'Connecting PHP to a MySQL database',         CURDATE() + INTERVAL 3 DAY,  '09:00:00', '10:00:00', 'Online',       'Scheduled',   'Y', 'Y', NOW() - INTERVAL 6 DAY),
(4,  4,  3, 2, 'Technology',      'Understanding JavaScript form validation',   CURDATE() - INTERVAL 1 DAY,  '15:00:00', '16:00:00', 'Online',       'Scheduled',   'Y', 'Y', NOW() - INTERVAL 7 DAY),
(5,  5,  1, 2, 'Technology',      'Making my portfolio site mobile friendly',   CURDATE(),                   '23:00:00', '23:30:00', 'Face-to-face', 'Scheduled',   'Y', 'Y', NOW() - INTERVAL 4 DAY),
(6,  6,  3, 2, 'Technology',      'Help with Python loops and functions',       NULL,                        NULL,       NULL,       'Online',       'Unscheduled', 'N', 'N', NOW() - INTERVAL 2 DAY),
(7,  7,  1, 2, 'Technology',      'Introduction to SQL joins',                  CURDATE() - INTERVAL 10 DAY, '14:00:00', '15:00:00', 'Online',       'Completed',   'Y', 'Y', NOW() - INTERVAL 12 DAY),
(8,  8,  3, 2, 'Technology',      'Setting up VS Code for web development',     CURDATE() - INTERVAL 4 DAY,  '09:00:00', '10:00:00', 'Online',       'Cancelled',   'Y', 'Y', NOW() - INTERVAL 9 DAY),
-- Yukie Lian (1) as VOLUNTEER
(9,  9,  2, 1, 'Academic',        'Statistics: hypothesis testing basics',      CURDATE() + INTERVAL 4 DAY,  '16:00:00', '17:00:00', 'Online',       'Pending',     'Y', 'N', NOW() - INTERVAL 4 DAY),
(10, 10, 3, 1, 'Academic',        'Essay structure for my English assignment',  CURDATE() + INTERVAL 6 DAY,  '10:00:00', '11:00:00', 'Online',       'Scheduled',   'Y', 'Y', NOW() - INTERVAL 5 DAY),
(11, 11, 2, 1, 'Academic',        'Algebra practice before my quiz',            CURDATE() - INTERVAL 2 DAY,  '14:00:00', '15:00:00', 'Online',       'Scheduled',   'Y', 'Y', NOW() - INTERVAL 7 DAY),
(12, 12, 3, 1, 'Academic',        'Probability practice questions',             NULL,                        NULL,       NULL,       'Online',       'Unscheduled', 'N', 'N', NOW() - INTERVAL 1 DAY),
(13, 13, 2, 1, 'Academic',        'Help with Calculus - differentiation',       CURDATE() - INTERVAL 7 DAY,  '13:00:00', '14:00:00', 'Online',       'Completed',   'Y', 'Y', NOW() - INTERVAL 9 DAY),
(14, 14, 3, 1, 'Academic',        'Reviewing my lab report',                    CURDATE() + INTERVAL 3 DAY,  '10:00:00', '11:00:00', 'Online',       'Rejected',    'Y', 'N', NOW() - INTERVAL 4 DAY),
-- Melvin (3) as VOLUNTEER
(15, 15, 1, 3, 'General Student', 'Planning my revision timetable',             CURDATE() + INTERVAL 1 DAY,  '14:00:00', '15:00:00', 'Online',       'Pending',     'Y', 'N', NOW() - INTERVAL 2 DAY),
(16, 16, 2, 3, 'General Student', 'Tips for presenting in front of a class',    CURDATE() + INTERVAL 2 DAY,  '13:00:00', '14:30:00', 'Online',       'Scheduled',   'Y', 'Y', NOW() - INTERVAL 4 DAY),
(17, 17, 1, 3, 'General Student', 'Managing deadlines across four subjects',    NULL,                        NULL,       NULL,       'Online',       'Unscheduled', 'N', 'N', NOW() - INTERVAL 1 DAY),
(18, 18, 2, 3, 'New Student',     'Finding the library and printing services',  NULL,                        NULL,       NULL,       'Face-to-face', 'Unscheduled', 'N', 'N', NOW() - INTERVAL 1 DAY),
(19, 19, 1, 3, 'General Student', 'Advice on managing my study timetable',      CURDATE() - INTERVAL 12 DAY, '10:00:00', '11:00:00', 'Online',       'Completed',   'Y', 'Y', NOW() - INTERVAL 13 DAY),
(20, 20, 2, 3, 'General Student', 'Preparing slides for my presentation',       CURDATE() - INTERVAL 15 DAY, '09:00:00', '10:00:00', 'Online',       'Completed',   'Y', 'Y', NOW() - INTERVAL 16 DAY),
(21, 21, 1, 3, 'New Student',     'Registering for next semester''s courses',   NULL,                        NULL,       NULL,       'Face-to-face', 'Cancelled',   'N', 'N', NOW() - INTERVAL 5 DAY);

-- Feedback (session 13 is left unrated so Low Huan Qi can rate it) -----------
INSERT INTO feedbacks (id, session_id, user_id, volunteer_id, rating, comments, submitted_at) VALUES
(1, 7,  1, 2, 5, 'Huan Qi explained SQL joins really clearly.',       NOW() - INTERVAL 9 DAY),
(2, 19, 1, 3, 4, 'Melvin gave practical tips for planning my week.',  NOW() - INTERVAL 11 DAY),
(3, 20, 2, 3, 5, 'Great presentation advice, thank you Melvin!',      NOW() - INTERVAL 14 DAY);

-- History for every finished session -----------------------------------------
INSERT INTO history (session_id, user_id, volunteer_id, feedback_id, final_status, completion_time, cancellation_reason) VALUES
(7,  1, 2, 1,    'Completed', NOW() - INTERVAL 10 DAY, NULL),
(8,  3, 2, NULL, 'Cancelled', NOW() - INTERVAL 5 DAY,  'Volunteer had a clash with a lecture.'),
(13, 2, 1, NULL, 'Completed', NOW() - INTERVAL 7 DAY,  NULL),
(14, 3, 1, NULL, 'Rejected',  NOW() - INTERVAL 1 DAY,  NULL),
(19, 1, 3, 2,    'Completed', NOW() - INTERVAL 12 DAY, NULL),
(20, 2, 3, 3,    'Completed', NOW() - INTERVAL 15 DAY, NULL),
(21, 1, 3, NULL, 'Cancelled', NOW() - INTERVAL 2 DAY,  'Student no longer needs help.');

-- Notifications ---------------------------------------------------------------
INSERT INTO notifications (user_id, message, notice_type, is_read, created_at) VALUES
(2, 'Yukie Lian requested a Technology session with you. Please accept or reject it in Session Request.', 'Session Requested', 'N', NOW() - INTERVAL 5 DAY),
(2, 'Melvin requested a Technology session with you. Please accept or reject it in Session Request.',     'Session Requested', 'N', NOW() - INTERVAL 5 DAY),
(2, 'Yukie Lian rated your Technology session 5/5.',                                                       'Feedback Received', 'Y', NOW() - INTERVAL 9 DAY),
(2, 'Melvin accepted your General Student session.',                                                       'Session Scheduled', 'N', NOW() - INTERVAL 4 DAY),
(2, 'Your Academic session is completed. Please leave feedback.',                                          'Feedback Reminder', 'N', NOW() - INTERVAL 7 DAY),
(1, 'Low Huan Qi accepted your Technology session.',                                                       'Session Scheduled', 'Y', NOW() - INTERVAL 6 DAY),
(1, 'Low Huan Qi requested an Academic session with you. Please accept or reject it in Session Request.', 'Session Requested', 'N', NOW() - INTERVAL 4 DAY),
(1, 'Melvin cancelled your New Student session.',                                                          'Session Cancelled', 'Y', NOW() - INTERVAL 2 DAY),
(1, 'Your Technology request has been sent to volunteers.',                                                'Request Submitted', 'Y', NOW() - INTERVAL 1 DAY),
(3, 'Yukie Lian requested a General Student session with you. Please accept or reject it in Session Request.', 'Session Requested', 'N', NOW() - INTERVAL 2 DAY),
(3, 'Yukie Lian rejected your Academic session.',                                                          'Session Rejected',  'N', NOW() - INTERVAL 1 DAY),
(3, 'Low Huan Qi rated your General Student session 5/5.',                                                 'Feedback Received', 'Y', NOW() - INTERVAL 14 DAY),
-- More variety: every notice type, spread over today, yesterday and earlier so the page shows its day groups
(2, 'Your Technology request has been sent to volunteers.',                                                'Request Submitted', 'N', NOW() - INTERVAL 20 MINUTE),
(2, 'Yukie Lian changed your Academic session to Sat, 24 Oct 2026, 9:00 AM - 10:00 AM.',                   'Session Updated',   'N', NOW() - INTERVAL 3 HOUR),
(2, 'You cancelled your Technology session with Melvin.',                                                  'Session Cancelled', 'N', NOW() - INTERVAL 1 DAY),
(2, 'Melvin rejected your General Student session on Fri, 23 Oct 2026 at 10:00 AM.',                       'Session Rejected',  'Y', NOW() - INTERVAL 2 DAY),
(2, 'Yukie Lian rated your Academic session 4/5.',                                                         'Feedback Received', 'Y', NOW() - INTERVAL 3 DAY),
(1, 'Low Huan Qi changed the Academic session to Sat, 24 Oct 2026, 9:00 AM - 10:00 AM. Please accept or reject it in Session Request.', 'Session Updated', 'N', NOW() - INTERVAL 40 MINUTE),
(1, 'Your Academic session is completed. Please leave feedback.',                                           'Feedback Reminder', 'N', NOW() - INTERVAL 1 DAY),
(3, 'Melvin, you have a new Technology request waiting for a volunteer.',                                  'Request Submitted', 'N', NOW() - INTERVAL 2 HOUR),
(3, 'Low Huan Qi accepted your Technology session.',                                                       'Session Scheduled', 'N', NOW() - INTERVAL 1 DAY);
