# Authentication System — DO NOT MODIFY

## CRITICAL: Protected Auth Components

The following authentication system is production-deployed and MUST NOT be overwritten, removed, or architecturally changed without explicit user authorization:

### Backend (Laravel)
- `app/Http/Controllers/AuthController.php` — Auth endpoints:
  - `register()` — Generates temp password, sends via Resend, no token returned
  - `login()` — Returns must_change_password in user object, checks is_active
  - `changePassword()` — Forced password change, revokes old tokens, issues new one
  - `logout()` — Revokes all tokens
- `app/Models/User.php` — Fillable includes must_change_password, role, is_active, phone; boolean casts
- `routes/api.php` — POST /change-password route under auth:sanctum middleware
- `config/services.php` — Resend API key configuration

### Frontend (React SPA)
- `resources/js/components/auth/LoginForm.jsx` — Login form with "Create Account" link to /register
- `resources/js/components/RegisterPage.jsx` — Registration form (name, email, phone — no password)
- `resources/js/components/auth/ChangePasswordModal.jsx` — Non-dismissable forced password change modal
- `resources/js/context/AuthContext.jsx` — Auth state with updateUser() for must_change_password
- `resources/js/components/layouts/DashboardLayout.jsx` — Renders ChangePasswordModal when must_change_password is true
- `resources/js/components/App.jsx` — /register route

### Database Schema
- `dev.users` table includes: must_change_password (boolean, default true), role (varchar, default 'user'), is_active (boolean, default true), phone (varchar)

## Enforced Auth Flow (MediCosts Paradigm)

1. Visitor clicks "Create Account" on login page
2. Enters: full name, email, phone (optional) — NO password field
3. Backend generates 12-char temp password (excludes I, l, O, 0)
4. Temp password emailed via Resend HTTP API (from: Aurora <noreply@acumenus.net>)
5. Visitor logs in with email + temp password → receives Sanctum token
6. Non-dismissable ChangePasswordModal blocks access until password changed
7. After password change: must_change_password = false, new token issued, full app access

## Rules

1. **NEVER remove the "Create Account" link from LoginForm.jsx**
2. **NEVER remove or make the ChangePasswordModal dismissable**
3. **NEVER bypass the must_change_password check in DashboardLayout**
4. **NEVER add password fields to RegisterPage** — temp passwords only
5. **NEVER change the email sender from noreply@acumenus.net**
6. **NEVER hardcode the Resend API key in source code** (use RESEND_API_KEY env var)
7. **NEVER remove email enumeration prevention** (register returns same message for existing/new emails)
8. **NEVER weaken password requirements** (min 8 chars, bcrypt 12 rounds)
9. **NEVER remove the is_active check from login** — inactive users must be denied
10. **Superuser account** `admin@acumenus.net` must always exist with must_change_password=false
11. **If modifying auth**, preserve ALL existing endpoints and their behavior — additions only
12. **NEVER revert register to accept user-chosen passwords** — the temp password + Resend email flow is mandatory
13. **NEVER break the LoginForm → axios.post('/api/login') flow** — it was fixed from a prior bug

## Resend Configuration
- API Key: RESEND_API_KEY in .env
- HTTP API call via Laravel Http facade in AuthController
- From: `Aurora <noreply@acumenus.net>`
