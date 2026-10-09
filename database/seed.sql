USE CampusConnect;

DELETE FROM notifications;
DELETE FROM history;
DELETE FROM feedbacks;
DELETE FROM sessions;
DELETE FROM requests;
DELETE FROM volunteers;
DELETE FROM users;

INSERT INTO users (id, email, full_name, university_name, password_hash, is_volunteer, is_active, created_at) VALUES
(1, 'aisha@help.edu.com.my',   'Aisha Rahman',  'Help University',             '$2y$10$ilRV9CW9s.7DbKu7c6lwTOcsUNW70NiCn3qzyp5A8fAUlstcrX9ei', 'N', 'Y', '2026-08-04 10:00:00'),
(2, 'ben@help.edu.com.my',     'Ben Tan',       'Help University',             '$2y$10$ilRV9CW9s.7DbKu7c6lwTOcsUNW70NiCn3qzyp5A8fAUlstcrX9ei', 'N', 'Y', '2026-08-19 10:00:00'),
(3, 'b2300554@help.edu.com.my',   'Low Huan Qi',  'Help University',             '$2y$10$ilRV9CW9s.7DbKu7c6lwTOcsUNW70NiCn3qzyp5A8fAUlstcrX9ei', 'Y', 'Y', '2026-09-13 10:00:00'),
(4, 'chandra@help.edu.com.my', 'Chandra Kumar', 'Help University',             '$2y$10$ilRV9CW9s.7DbKu7c6lwTOcsUNW70NiCn3qzyp5A8fAUlstcrX9ei', 'Y', 'Y', '2026-07-05 10:00:00'),
(5, 'dinesh@help.edu.com.my',  'Dinesh Raj',    'Help University',             '$2y$10$ilRV9CW9s.7DbKu7c6lwTOcsUNW70NiCn3qzyp5A8fAUlstcrX9ei', 'Y', 'Y', '2026-07-15 10:00:00'),
(6, 'elena@help.edu.com.my',   'Elena Wong',    'Help University',             '$2y$10$ilRV9CW9s.7DbKu7c6lwTOcsUNW70NiCn3qzyp5A8fAUlstcrX9ei', 'N', 'N', '2026-09-03 10:00:00');

INSERT INTO requests (id, user_id, volunteer_id, category, description, preferred_day, preferred_time, support_mode, status, created_at, notified_at, responded_at) VALUES
(1, 1, 3,    'Academic',        'Help with Maths - Algebra basics',                     'Monday',    'Morning',   'Online',       'Accepted', '2026-09-23 10:00:00', '2026-09-23 10:00:00', '2026-09-24 10:00:00'),
(2, 2, NULL, 'Technology',      'Debugging my first PHP project',                       'Tuesday',   'Afternoon', 'Face-to-face', 'Pending',   '2026-10-02 10:00:00', '2026-10-02 10:00:00', NULL),
(3, 2, 4,    'Academic',        'Understanding Newton''s laws of motion',               'Wednesday', 'Afternoon', 'Face-to-face', 'Accepted', '2026-09-28 10:00:00', '2026-09-28 10:00:00', '2026-09-29 10:00:00'),
(4, 1, 3,    'Academic',        'Hypothesis testing for my Statistics assignment',      'Friday',    'Afternoon', 'Online',       'Accepted', '2026-09-19 10:00:00', '2026-09-19 10:00:00', '2026-09-20 10:00:00'),
(5, 5, 4,    'Technology',      'Reviewing my Java data structures project',            'Thursday',  'Morning',   'Online',       'Accepted', '2026-09-25 10:00:00', '2026-09-25 10:00:00', '2026-09-26 10:00:00'),
(6, 2, 5,    'General Student', 'Advice on managing my study timetable',                'Monday',    'Afternoon', 'Online',       'Accepted', '2026-10-01 10:00:00', '2026-10-01 10:00:00', '2026-10-02 10:00:00'),
(7, 1, NULL, 'New Student',     'Finding my way around campus and registering courses', 'Saturday',  'Morning',   'Face-to-face', 'Pending',   '2026-10-03 07:00:00', '2026-10-03 07:00:00', NULL);

INSERT INTO volunteers (user_id, category, preferred_day, preferred_time, support_mode, is_available) VALUES
(3, 'Academic',        'Sunday',    '09:00:00', 'Online',       'Y'),
(3, 'Academic',        'Monday',    '10:00:00', 'Online',       'Y'),
(3, 'Academic',        'Wednesday', '11:00:00', 'Online',       'Y'),
(3, 'Academic',        'Friday',    '15:00:00', 'Online',       'Y'),
(3, 'New Student',     'Saturday',  '10:30:00', 'Face-to-face', 'Y'),
(4, 'Academic',        'Monday',    '14:00:00', 'Face-to-face', 'Y'),
(4, 'Technology',      'Tuesday',   '10:00:00', 'Online',       'Y'),
(4, 'Technology',      'Thursday',  '09:00:00', 'Online',       'Y'),
(4, 'Technology',      'Sunday',    '16:00:00', 'Online',       'N'),
(5, 'General Student', 'Monday',    '16:00:00', 'Online',       'Y'),
(5, 'General Student', 'Thursday',  '13:00:00', 'Online',       'Y');

INSERT INTO sessions (id, request_id, user_id, volunteer_id, category, description, session_date, start_time, end_time, support_mode, status, conf_student, conf_volunteer, created_at) VALUES
(1, 1, 1, 3, 'Academic',        'Help with Maths - Algebra basics',          '2026-10-04', '09:00:00', NULL,       'Online',       'Cancelled', 'Y', 'N', '2026-09-25 10:00:00'),
(2, 3, 2, 4, 'Academic',        'Understanding Newton''s laws of motion',    '2026-10-05', '14:00:00', '15:00:00', 'Face-to-face', 'Scheduled', 'Y', 'Y', '2026-09-29 10:00:00'),
(3, 4, 1, 3, 'Academic',        'Statistics: t-test and p-values',           '2026-09-26', '15:00:00', '16:00:00', 'Online',       'Completed', 'Y', 'Y', '2026-09-21 10:00:00'),
(4, 5, 5, 4, 'Technology',      'Reviewing my Java data structures project', '2026-09-30', '09:00:00', '10:30:00', 'Online',       'Completed', 'Y', 'Y', '2026-09-27 10:00:00'),
(5, 6, 2, 5, 'General Student', 'Advice on managing my study timetable',     NULL,         NULL,       NULL,       'Online',       'Pending',   'N', 'N', '2026-10-02 10:00:00');

INSERT INTO feedbacks (id, session_id, user_id, volunteer_id, rating, comments, submitted_at) VALUES
(1, 3, 1, 3, 5, 'Huan Qi explained p-values really clearly. Very helpful!', '2026-09-27 10:00:00');

INSERT INTO history (session_id, user_id, volunteer_id, feedback_id, final_status, completion_time, cancellation_reason) VALUES
(1, 1, 3, NULL, 'Cancelled', '2026-09-26 10:00:00', 'Student had a clash with a lecture.'),
(3, 1, 3, 1,    'Completed', '2026-09-26 10:00:00', NULL),
(4, 5, 4, NULL, 'Completed', '2026-09-30 10:00:00', NULL);

INSERT INTO notifications (user_id, message, notice_type, is_read, created_at) VALUES
(1, 'Low Huan Qi accepted your Academic request.',                   'Request Accepted',  'Y', '2026-09-24 10:00:00'),
(3, 'Aisha Rahman cancelled the Academic session.',                  'Session Cancelled', 'Y', '2026-09-26 10:00:00'),
(2, 'Your Academic session with Chandra Kumar is booked.',           'Session Scheduled', 'N', '2026-09-29 10:00:00'),
(4, 'Ben Tan booked an Academic session with you.',                  'Session Scheduled', 'Y', '2026-09-29 10:00:00'),
(3, 'Aisha Rahman rated your Academic session 5/5.',                 'Feedback Received', 'N', '2026-09-27 10:00:00'),
(5, 'Your Technology session is completed. Please leave feedback.',  'Feedback Reminder', 'N', '2026-09-30 10:00:00'),
(2, 'Dinesh Raj accepted your General Student request.',             'Request Accepted',  'N', '2026-10-02 10:00:00'),
(2, 'Your Technology request has been sent to volunteers.',          'Request Submitted', 'Y', '2026-10-02 10:00:00'),
(1, 'Your New Student request has been sent to volunteers.',         'Request Submitted', 'N', '2026-10-03 07:00:00');
