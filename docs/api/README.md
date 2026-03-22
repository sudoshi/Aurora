# Aurora API Reference

Base URL: `https://aurora.acumenus.net/api`

All authenticated endpoints require a `Authorization: Bearer {token}` header (Laravel Sanctum).

---

## Authentication

| Method | Endpoint | Rate Limit | Description |
|--------|----------|------------|-------------|
| POST | `/auth/register` | 3/min | Register (name, email, phone). Temp password emailed via Resend. |
| POST | `/auth/login` | 5/min | Login with email + password. Returns Sanctum token. |
| GET | `/auth/user` | -- | Get authenticated user profile. |
| POST | `/auth/logout` | -- | Revoke all tokens. |
| POST | `/auth/change-password` | -- | Change password (forced on first login). |

### Auth Flow

1. Register with name + email (no password field).
2. Receive temp password via email.
3. Login with email + temp password.
4. Forced password change modal appears (non-dismissable).
5. After password change, full access granted.

---

## Dashboard

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/dashboard/stats` | Aggregated dashboard statistics. |

---

## Case Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/cases` | List cases (paginated). Filters: `status`, `specialty`, `urgency`, `search`, `per_page`. |
| POST | `/cases` | Create a new case. |
| GET | `/cases/{id}` | Show case with all relations. |
| PUT | `/cases/{id}` | Update a case. |
| DELETE | `/cases/{id}` | Archive/soft-delete a case. |
| POST | `/cases/{id}/team` | Add team member (`user_id`, `role`). |
| DELETE | `/cases/{id}/team/{userId}` | Remove team member. |

### Case Sub-Resources

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/cases/{id}/discussions` | List discussions. |
| POST | `/cases/{id}/discussions` | Create discussion (supports threads via `parent_id`). |
| GET | `/cases/{id}/annotations` | List annotations. |
| POST | `/cases/{id}/annotations` | Create annotation. |
| GET | `/cases/{id}/documents` | List documents. |
| POST | `/cases/{id}/documents` | Upload document (rate limit: 10/min). |
| DELETE | `/documents/{id}` | Delete document. |

---

## Sessions

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/sessions` | List sessions. Filters: `status`, `session_type`, `per_page`. |
| POST | `/sessions` | Create session. |
| GET | `/sessions/{id}` | Show session with cases and participants. |
| PUT | `/sessions/{id}` | Update session. |
| DELETE | `/sessions/{id}` | Delete session. |
| POST | `/sessions/{id}/start` | Transition to `live`. |
| POST | `/sessions/{id}/end` | Transition to `completed`. |
| POST | `/sessions/{id}/cases` | Add case to session queue. |
| PATCH | `/sessions/{id}/cases/{sessionCaseId}` | Update case order/status in queue. |
| DELETE | `/sessions/{id}/cases/{sessionCaseId}` | Remove case from session. |
| POST | `/sessions/{id}/join` | Join as participant. |
| POST | `/sessions/{id}/leave` | Leave session. |

---

## Decisions

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/cases/{id}/decisions` | List decisions for a case. |
| POST | `/cases/{id}/decisions` | Propose a decision. |
| PATCH | `/decisions/{id}` | Update decision. |
| POST | `/decisions/{id}/vote` | Cast vote (agree/disagree/abstain). |
| POST | `/decisions/{id}/finalize` | Finalize (approved/rejected/deferred). |
| POST | `/decisions/{id}/follow-ups` | Add follow-up task. |
| PATCH | `/follow-ups/{id}` | Update follow-up status. |

---

## Patients

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/patients/search` | Search patients by name or MRN. |
| GET | `/patients/{id}/profile` | Full patient profile with clinical data. |
| GET | `/patients/{id}/stats` | Patient statistics summary. |
| POST | `/patients` | Create patient record. |

### Imaging

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/patients/{id}/imaging` | List imaging studies. |
| GET | `/patients/{id}/imaging/response-assessments` | RECIST response assessments. |
| GET | `/patients/{id}/imaging/{studyId}` | Show imaging study detail. |
| POST | `/patients/{id}/imaging/{studyId}/measurements` | Record measurement. |

---

## AI Service (rate limit: 30/min)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/ai/{path}` | Proxy POST to FastAPI AI service. |
| GET | `/ai/{path}` | Proxy GET to FastAPI AI service. |

### Abby (Conversation AI)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/abby/conversations` | List conversations. |
| POST | `/abby/conversations` | Create conversation. |
| GET | `/abby/conversations/{id}` | Show conversation with messages. |
| DELETE | `/abby/conversations/{id}` | Delete conversation. |
| POST | `/abby/chat` | Send message to Abby. |
| POST | `/abby/conversations/{id}/title` | Auto-generate conversation title. |

---

## Commons Workspace

### Channels

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/commons/channels` | List channels. |
| POST | `/commons/channels` | Create channel. |
| GET | `/commons/channels/{slug}` | Show channel. |
| PATCH | `/commons/channels/{slug}` | Update channel. |
| POST | `/commons/channels/{slug}/archive` | Archive channel. |

### Messages

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/commons/channels/{slug}/messages` | List messages in channel. |
| POST | `/commons/channels/{slug}/messages` | Send message. |
| GET | `/commons/messages/search` | Full-text search across messages. |
| PATCH | `/commons/messages/{id}` | Edit message. |
| DELETE | `/commons/messages/{id}` | Delete message. |
| GET | `/commons/channels/{slug}/messages/{id}/replies` | Get thread replies. |

### Members, Reactions, Pins, DMs

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/commons/channels/{slug}/members` | List members. |
| POST | `/commons/channels/{slug}/members` | Add member. |
| POST | `/commons/channels/{slug}/read` | Mark channel as read. |
| POST | `/commons/messages/{id}/reactions` | Toggle reaction. |
| GET | `/commons/channels/{slug}/pins` | List pinned messages. |
| GET | `/commons/dm` | List DM threads. |
| POST | `/commons/dm` | Send direct message. |

### Attachments, Reviews, Wiki, Announcements

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/commons/channels/{slug}/attachments` | Upload file. |
| GET | `/commons/attachments/{id}/download` | Download file. |
| POST | `/commons/channels/{slug}/reviews` | Create review request. |
| PATCH | `/commons/reviews/{id}/resolve` | Resolve review. |
| GET | `/commons/wiki` | List wiki pages. |
| POST | `/commons/wiki` | Create wiki page. |
| GET | `/commons/announcements` | List announcements. |
| POST | `/commons/announcements` | Create announcement. |

### Notifications

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/commons/notifications` | List notifications. |
| GET | `/commons/notifications/unread-count` | Unread count. |
| POST | `/commons/notifications/mark-read` | Mark as read. |

---

## Admin (requires `admin` or `super-admin` role)

### User Management

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/users` | List users. |
| POST | `/admin/users` | Create user. |
| GET | `/admin/users/{id}` | Show user. |
| PUT | `/admin/users/{id}` | Update user. |
| DELETE | `/admin/users/{id}` | Delete user. |
| PUT | `/admin/users/{id}/roles` | Sync roles. |
| GET | `/admin/users/{id}/audit` | User audit trail. |

### Audit Logs

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/user-audit` | List all audit logs. |
| GET | `/admin/user-audit/summary` | Audit summary. |

### Roles & Permissions (super-admin only)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/roles` | List roles. |
| POST | `/admin/roles` | Create role. |
| GET | `/admin/roles/permissions` | List all permissions. |
| GET | `/admin/roles/{id}` | Show role. |
| PUT | `/admin/roles/{id}` | Update role. |
| DELETE | `/admin/roles/{id}` | Delete role. |

### AI Providers (super-admin only)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/ai-providers` | List providers. |
| GET | `/admin/ai-providers/{type}` | Show provider config. |
| PUT | `/admin/ai-providers/{type}` | Update provider config. |
| POST | `/admin/ai-providers/{type}/enable` | Enable provider. |
| POST | `/admin/ai-providers/{type}/disable` | Disable provider. |
| POST | `/admin/ai-providers/{type}/activate` | Set as active. |
| POST | `/admin/ai-providers/{type}/test` | Test connectivity. |

### System Health

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/system-health` | All health checks. |
| GET | `/admin/system-health/{key}` | Specific health check. |

### App Settings

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/app-settings` | Get application settings. |
| PATCH | `/app-settings` | Update settings (super-admin). |
