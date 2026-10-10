<<<<<<< Updated upstream
# CampusConnect
BIT 216 Project 2 Campus Connect
=======
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
Register -> Account Created -> Login with Email + Password -> Profile Dashboard

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

## Microsoft Outlook OTP email (Microsoft Graph)

OTP messages for password reset and password change are sent using Microsoft Graph. The app does not display demo codes. Real delivery requires an Outlook/Microsoft 365 mailbox and a Microsoft Entra app registration with Graph `Mail.Send` **application** permission and admin consent. The tenant administrator may need to approve the permission. Microsoft documents the [Graph sendMail API](https://learn.microsoft.com/graph/api/user-sendmail?view=graph-rest-1.0) and [client credentials flow](https://learn.microsoft.com/entra/identity-platform/v2-oauth2-client-creds-grant-flow).

1. Register a single-tenant app in Microsoft Entra ID.
2. Add Microsoft Graph `Mail.Send` under **Application permissions**, then grant admin consent.
3. Create a client secret and copy its **Value** (not its Secret ID).
4. Copy `backend/general/mail_config.example.php` to `backend/general/mail_config.php` and set the tenant ID, app client ID, secret value, and sender mailbox address. The sender must be a mailbox in the app's tenant.
5. Confirm PHP's cURL extension is enabled, then restart Apache and request a new OTP.

The app requests a Graph access token with the client-credentials flow and calls `POST /users/{sender}/sendMail`. It shows a configuration error if Microsoft does not accept the send request. Keep the client secret private; `mail_config.php` is excluded from Git. Application `Mail.Send` is powerful, so an administrator should restrict the app to the designated sender mailbox where the tenant's Exchange configuration supports it.

Profile photo uploads are saved under `uploads/profile/`; the folder is created on the first successful upload.
>>>>>>> Stashed changes
