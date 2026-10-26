# Campus Connect

BIT 216 Project 2. A peer-support web app where students request help and volunteers run support sessions.

Built with plain PHP 8.2, MySQL and vanilla JavaScript, running on XAMPP.

## Contents

- [Getting started](#getting-started)
- [Optional setup](#optional-setup)
- [Features and flows](#features-and-flows)
- [Project layout](#project-layout)
- [Styles and scripts](#styles-and-scripts)
- [Notes for developers](#notes-for-developers)

## Getting started

1. Put this folder in `C:\xampp\htdocs\CampusConnect`.
2. Start **Apache** and **MySQL** in the XAMPP Control Panel.
3. Make sure Apache's `mod_rewrite` module is enabled and `.htaccess` overrides are allowed. The root `.htaccess` maps short URLs such as `/login.php` to the pages in `backend/`.
4. In phpMyAdmin, import `database/schema.sql`. It creates the `CampusConnect` database, all tables and the sample data.
5. Open `http://localhost/CampusConnect/register.php`, create an account with an email you can access, then log in. Email codes need [Gmail OTP email](#gmail-otp-email) to be set up.

To reset the database, drop `CampusConnect` and import `schema.sql` again. This replaces all data.

## Optional setup

All local settings live in `backend/general/`. These files are excluded from Git; never commit them.

| File | Purpose |
|---|---|
| `config.local.php` | Overrides `config.php` on your machine (database login, `BASE_URL`, `$DEV_TOOLS`) |
| `mail_config.php` | Gmail SMTP settings for emailed codes (copy `mail_config.example.php`) |
| `google_config.php` | Google Calendar sync credentials |

### Gmail OTP email

Registration, password reset and password change send 6-digit codes through Gmail SMTP to the account's email. The recipient can use any email provider.

1. Turn on 2-Step Verification for the Gmail account that sends the messages.
2. Create a Google App Password for Campus Connect ([Google's instructions](https://support.google.com/mail/answer/185833)).
3. Copy `mail_config.example.php` to `mail_config.php`. Set `sender_email` to the sending Gmail address and `app_password` to the App Password, without spaces. Never use your normal Gmail password.
4. Make sure PHP's cURL extension is enabled, restart Apache, and request a new code.

The sender and recipient can be the same account. Mail is sent over authenticated SMTP with TLS on port 587 ([Gmail SMTP settings](https://support.google.com/mail/answer/7104828)).

### Google Calendar sync

When `google_config.php` holds `client_id`, `client_secret`, `redirect_uri` and `organizer_user_id`, confirmed sessions are added to Google Calendar and both people get an invite. Only the organizer account connects; everyone else receives invites automatically.

Set `$DEV_TOOLS = true` in `config.local.php` to show the connect panel on the Session Dashboard. Without a complete config, sync is simply off.

## Features and flows

### Accounts

- **Register:** any email you can access → 6-digit verification code → account created → log in.
- **Log in:** email and password. 3 wrong passwords lock the account for 15 minutes. "Remember me" keeps you signed in for 14 days.
- **Forgot password:** email → 6-digit reset code → new password → log in.
- **Change password:** current password → 6-digit code → new password → done.

### Profile

- **Profile picture:** JPG or PNG up to 2MB, previewed before saving.
- **Cover photo:** a separate JPG or PNG up to 5MB.
- **Defaults:** users without their own images see `uploads/profile/profile_default.png` and `uploads/covers/cover_default.png`, set in `config.php` as `DEFAULT_PROFILE_PICTURE` and `DEFAULT_COVER_PHOTO`.
- **Roles:** a user can become a volunteer and switch between Student and Volunteer. The Session and History pages always show records from the current role only.

### Requests and sessions

1. A student creates a support request and chooses a volunteer.
2. The volunteer accepts the request, which creates an unscheduled session.
3. The session is booked: date, time slot and Online or Face-to-face.
4. The volunteer accepts or rejects the booked time. **Rejecting requires a reason.**
5. Either person can cancel an active session. **Cancelling requires a reason.**
6. After the session ends, the volunteer marks it complete.

Reasons are stored with the history record and included in the other person's notification.

### History and feedback

- **History** lists Completed, Cancelled and Rejected sessions for the current role.
- **History Details** shows both people, the outcome and its reason, the date and time, and any feedback.
- **Rate Your Session:** after completion, the student rates the session from 1 to 5 stars and can add comments (up to 500 characters). Volunteers see "Not rated" until the student rates it.

### Notifications

Session events (requested, scheduled, updated, rejected, cancelled) create notifications. Clicking a notification opens that session's details page. See [Notification session tag](#notification-session-tag) below.

## Project layout

```
CampusConnect/
├── .htaccess                  Short public URLs → backend/ pages
├── index.php
├── backend/                   All PHP, grouped by feature
│   ├── general/               Shared code (config, DB, auth, layout, sidebars, notifications)
│   ├── user_management/       Login, register, logout, password reset and change
│   ├── profile_management/    Profile, edit profile, pictures, volunteer profile
│   ├── request_management/    Support requests
│   ├── session_management/    Booking, session dashboard, details, accept/reject/cancel, calendar
│   ├── history_management/    History, history details, rating
│   └── notification_management/  Notifications page
├── frontend/
│   ├── css/                   Stylesheets (see below)
│   ├── js/                    Browser-side interactions
│   └── images/                Logo and campus photo
├── database/
│   └── schema.sql             All tables plus sample data
└── uploads/                   User images (profile/, covers/)
```

Key shared files in `backend/general/`:

| File | What it does |
|---|---|
| `config.php` | Single config: database, `BASE_URL`, mail, default images |
| `db.php`, `auth.php` | PDO connection; `requireLogin()` and `currentUser()` |
| `header.php`, `footer.php` | Page layout for feature pages |
| `navigation.php`, `account_sidebar.php`, `nav_icons.php` | The two sidebars and their shared icons |
| `dashboard_header.php` | Top bar with notifications bell, avatar and role badge |
| `card_template.php`, `format.php` | List cards, detail lists, date and time formatting |
| `notifications.php` | Create, count, list and mark notifications |

## Styles and scripts

| File | Used for |
|---|---|
| `style.css` | Shared base styles, account and auth pages, dashboard and sidebar |
| `navigation.css` | Layout of the feature pages (sidebar shell) |
| `profile.css` | Profile, volunteer and image upload pages |
| `session.css` | Session cards, booking, session details, confirm popups |
| `history.css` | History list, history details, rating page (loaded after `session.css`) |

Load `style.css` before page-specific files so they can refine shared components.

`session.css` and `history.css` are linked with a version stamp (`?v=<file modified time>`), so browsers fetch the new file as soon as it changes. Other stylesheets may need a hard refresh (Ctrl+F5) after edits.

`frontend/js/script.js` drives the booking calendar and time grid. `navigation.js` handles the sidebar.

## Notes for developers

### Notification session tag

The `notifications` table has no session column. To link a notice to a session, `addNotification()` appends a hidden tag to the message:

```
You cancelled your Technology session with Yukie Lian. [session:12]
```

`splitNotificationMessage()` in `general/notifications.php` removes the tag and returns the session id. **Any page that displays notification text must call it**, or users will see the tag. Notices without a tag link to the matching list page instead.

### Reason prompts in confirm popups

`CONFIRM_MODALS` in `session_management/result_modal.php` defines the "Are you sure?" popups. An optional sixth item adds a required reason box with its own label and quick reasons. The text is posted as `reason`, and the action page must check it.

### Rules

- Local secrets stay out of Git: `config.local.php`, `mail_config.php`, `google_config.php`.
- Avoid committing private user uploads in `uploads/`.
- New feature pages call `requireLogin()` from `general/auth.php`.
