# CampusConnect
BIT 216 Project 2 Campus Connect
# Campus Connect - Use Case 1

## Project layout

- `backend/` contains all PHP, grouped by feature:
  - `general/` — shared code: `config.php` (the single config: database, `BASE_URL`, mail settings), `db.php` (PDO connection), `account_functions.php` (account helpers), `auth.php`, page layout, sidebars, card template, notifications.
  - `user_management/` — login, register, logout, password reset, change password.
  - `profile_management/` — profile, edit profile, profile/cover pictures, volunteer profile.
  - `request_management/`, `session_management/`, `history_management/` — support requests, sessions, and history.
- The root `.htaccess` keeps the short public URLs (e.g. `/login.php`) and maps them to the pages in `backend/`.
- `frontend/css/` contains shared styles (`style.css`), profile and volunteer page styles (`profile.css`), backend navigation styles (`navigation.css`), and session page styles (`session.css`).
- `frontend/js/` contains browser-side interactions.
- `database/` contains `schema.sql` (all tables) and `seed.sql` (sample data). Everything uses the `CampusConnect` database.
- `uploads/` stores user-uploaded images; avoid committing private uploads.
- Local settings go in `backend/general/config.local.php` and `backend/general/mail_config.php` (copy `mail_config.example.php`). Both are excluded from Git; never commit secrets.

## CSS organization

`style.css` owns shared legacy module styles and current account, authentication, security, dashboard, and sidebar styles. `profile.css` owns profile, volunteer, and image upload screens. `navigation.css` is used by the original backend modules. Load `style.css` before `profile.css` on profile pages so page-specific rules can refine shared components.

Flow:
Register with an email address you can access (including Gmail) -> Account Created -> Login with Email + Password -> Profile Dashboard

Login locks an account for 15 minutes after 3 incorrect passwords. “Remember me” keeps the browser signed in for 14 days.

Forgot Password -> Email -> 6-Digit Reset Code -> New Password -> Login

Change Password -> Current Password -> 6-Digit OTP / 2FA -> New Password -> Password Changed -> Profile Dashboard

Profile Picture -> Choose or drop a JPG/PNG image (2MB maximum) -> Preview -> Save

Profile Cover Photo -> Choose a separate JPG/PNG cover image (5MB maximum) -> Preview -> Save

## XAMPP
1. Put this folder in `C:\xampp\htdocs\CampusConnect`.
2. Start Apache and MySQL.
3. Make sure Apache's `mod_rewrite` module is enabled and `.htaccess` overrides are allowed; the root `.htaccess` maps the short PHP URLs to the pages in `backend/`.
4. In phpMyAdmin, import `database/schema.sql` (creates the `CampusConnect` database), then `database/seed.sql` for sample data. Sample accounts use the password `welcome@123`.
5. To update an older database, drop `CampusConnect` and import both files again (this replaces all data).
6. Open `http://localhost/CampusConnect/register.php`.

## Gmail OTP email

OTP messages for password reset and password change are sent through Gmail SMTP to the account email on file. The recipient may use Gmail or another email provider. To enable sending:

1. Turn on 2-Step Verification for the Gmail account that will send the messages.
2. Create a Google App Password for Campus Connect. Google requires 2-Step Verification for App Passwords; see [Google's App Password instructions](https://support.google.com/mail/answer/185833).
3. Copy `backend/general/mail_config.example.php` to `backend/general/mail_config.php`, then set `sender_email` to the sending Gmail address and `app_password` to the generated App Password (remove spaces if present). Never use your regular Gmail password.
4. Confirm PHP's cURL extension is enabled, restart Apache, and request a new OTP.

The sender and recipient may be the same Gmail account. Keep the App Password private; `mail_config.php` is excluded from Git. The connection uses Gmail's authenticated SMTP service with TLS on port 587 ([Gmail SMTP settings](https://support.google.com/mail/answer/7104828)).

Profile photo uploads are saved under `uploads/profile/`; the folder is created on the first successful upload.

