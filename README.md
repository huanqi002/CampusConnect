# Campus Connect - Use Case 1

## Project layout

- `backend/` contains the feature modules, grouped by general utilities, users, requests, sessions, and history.
- `frontend/css/` contains shared styles (`style.css`), profile and volunteer page styles (`profile.css`), and backend navigation styles (`navigation.css`).
- `frontend/js/` contains browser-side interactions.
- `auth/`, `profile/`, and `security/` hold page implementations by feature. `routes/` contains categorized entry points, while the root `.htaccess` preserves the existing public URLs. `includes/` contains shared configuration, database, and helper code; `includes/legacy/` keeps the unused legacy sidebar separate. `config/` holds the safe mail configuration example.
- `database/` contains the base schema and feature-specific SQL patches.
- `uploads/` stores user-uploaded images; avoid committing private uploads.
- `includes/config.php` and the local root-level `mail_config.php` hold settings. Keep secrets in local config files, never in source control.

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
3. Make sure Apache's `mod_rewrite` module is enabled and `.htaccess` overrides are allowed; the root `.htaccess` maps the existing PHP URLs to `routes/`.
4. Import the group's `schema.sql` into phpMyAdmin.
5. Run `database/schema_usecase1_patch.sql` in `support_system`.
6. Open `http://localhost/CampusConnect/register.php`.

## Microsoft Outlook OTP email (Microsoft Graph)

OTP messages for password reset and password change are sent using Microsoft Graph. The app does not display demo codes. Real delivery requires an Outlook/Microsoft 365 mailbox and a Microsoft Entra app registration with Graph `Mail.Send` **application** permission and admin consent. The tenant administrator may need to approve the permission. Microsoft documents the [Graph sendMail API](https://learn.microsoft.com/graph/api/user-sendmail?view=graph-rest-1.0) and [client credentials flow](https://learn.microsoft.com/entra/identity-platform/v2-oauth2-client-creds-grant-flow).

1. Register a single-tenant app in Microsoft Entra ID.
2. Add Microsoft Graph `Mail.Send` under **Application permissions**, then grant admin consent.
3. Create a client secret and copy its **Value** (not its Secret ID).
4. Copy `config/mail_config.example.php` to `mail_config.php` and set the tenant ID, app client ID, secret value, and sender mailbox address. The sender must be a mailbox in the app's tenant.
5. Confirm PHP's cURL extension is enabled, then restart Apache and request a new OTP.

The app requests a Graph access token with the client-credentials flow and calls `POST /users/{sender}/sendMail`. It shows a configuration error if Microsoft does not accept the send request. Keep the client secret private; `mail_config.php` is excluded from Git. Application `Mail.Send` is powerful, so an administrator should restrict the app to the designated sender mailbox where the tenant's Exchange configuration supports it.

Profile photo uploads are saved under `uploads/profile/`; the folder is created on the first successful upload.
