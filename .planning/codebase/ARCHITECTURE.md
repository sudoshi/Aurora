# Architecture

**Analysis Date:** 2026-03-24

## Pattern Overview

**Overall:** Multi-tier distributed architecture (REST API + SPA + Python AI service)

**Key Characteristics:**
- Layered monolith backend (Controllers → Services → Models → Database)
- Feature-based frontend SPA with client-side state management
- Separate Python FastAPI service for AI reasoning (Abby)
- Database-driven design with PostgreSQL (multiple schemas: app, clinical, commons)
- Token-based authentication (Laravel Sanctum)
- Real-time collaboration support via WebSockets (Laravel Reverb)

## Layers

**Controller Layer:**
- Purpose: HTTP request handling and route dispatch
- Location: `backend/app/Http/Controllers/`
- Contains: 39 controllers organized by domain (Admin, Commons, clinical features)
- Depends on: Services, Models, Form Requests
- Used by: Route definitions in `routes/api.php`
- Patterns: Dependency injection via constructor, request validation delegated to Form Requests

**Service Layer:**
- Purpose: Business logic encapsulation and multi-model operations
- Location: `backend/app/Services/`
- Contains: PatientService, CaseService, AuthService, CaseDiscussionService, EventService, genomics services
- Depends on: Models, external contracts (ClinicalDataAdapter), utilities
- Used by: Controllers and other services for cross-cutting concerns
- Pattern: Services accept primitive types or models, return models or arrays; constructor injection of dependencies

**Model Layer:**
- Purpose: Data representation and relationships
- Location: `backend/app/Models/` (root) and `backend/app/Models/Clinical/` (domain models)
- Contains: 30+ Eloquent models (User, ClinicalCase, ClinicalPatient, CaseDiscussion, clinical data models)
- Depends on: Eloquent, Laravel traits (HasRoles, HasFactory, SoftDeletes)
- Relationships: BelongsTo, HasMany, HasManyThrough connections between models
- Scopes: Query builder scopes (e.g., `scopeActive()`, `scopeForUser()`) for common filters

**Data Access Layer:**
- Purpose: Adapter pattern for clinical data from multiple sources
- Location: `backend/app/Contracts/ClinicalDataAdapter.php`, `backend/app/Services/Adapters/`
- Contains: Interface defining data retrieval contracts (getPatient, getConditions, getMedications, etc.)
- Implementations: ManualAdapter (default), FHIR adapter, OMOP adapter (pluggable)
- Pattern: Strategy pattern — services depend on interface, not implementation

**Database Layer:**
- Purpose: Data persistence with domain separation
- Location: `backend/database/migrations/`
- Schemas: `app` (users, auth, cases, sessions), `clinical` (patient data, conditions, meds, imaging, genomics), `commons` (collaboration)
- Constraints: Foreign keys, indexes on high-query columns, soft deletes for auditable entities

**Frontend State Management:**
- Purpose: Client-side state synchronization
- Location: `frontend/src/stores/`
- Contains: Zustand stores (authStore, profileStore, uiStore, abbyStore) with persist middleware
- Pattern: Immutable state updates, selectors for derived state

**Frontend API Layer:**
- Purpose: HTTP client abstraction
- Location: `frontend/src/lib/api-client.ts`, `frontend/src/features/*/api.ts`
- Contains: Axios instance with Sanctum token injection, feature-specific API functions
- Pattern: Each feature exports typed query/mutation hooks using TanStack Query

**AI Service Layer:**
- Purpose: Clinical reasoning and conversational assistance
- Location: `ai/app/routers/abby.py`
- Contains: FastAPI routes for case analysis, conversational chat, streaming responses
- Depends on: MedGemma (via Ollama), session state management
- Pattern: Pydantic validation, streaming responses via FastAPI StreamingResponse

## Data Flow

**Authentication Flow:**
1. User submits registration (name, email, phone) → AuthController.register()
2. AuthService generates 12-char temp password, creates user with must_change_password=true
3. Password emailed via Resend API
4. User logs in with email + temp password → AuthController.login()
5. AuthService validates credentials, checks is_active flag, returns Sanctum token
6. Frontend stores token in Zustand authStore (persisted)
7. API client injects token in Authorization header for all subsequent requests
8. On forced password change → ChangePasswordModal (non-dismissable) in DashboardLayout
9. AuthService.changePassword() revokes old tokens, issues new one, sets must_change_password=false

**Patient Profile Retrieval:**
1. Frontend: usePatientProfile hook (TanStack Query) → GET /api/patients/{id}/profile
2. Backend: PatientController.profile() → PatientService.getProfile(patientId)
3. PatientService uses ClinicalDataAdapter (strategy) to fetch full patient data
4. Adapter queries clinical schema: conditions, medications, procedures, measurements, observations, visits, notes, imaging, genomics
5. Service enriches with patient stats (counts per domain)
6. Controller formats via ApiResponse helper: `{success: true, data: {...}, message: string}`
7. Frontend receives typed response, TanStack Query caches by key

**Case Discussion Collaboration:**
1. Multiple users join CaseDiscussionPage for same case_id
2. WebSocket (Laravel Reverb) broadcasts:
   - New CaseDiscussion messages (threaded)
   - Reactions (emoji) on messages
   - Review requests (async feedback)
   - Annotations on patient data
3. Frontend: useQuery hooks poll for updates; useWebSocket listener for real-time
4. CaseDiscussionService.addMessage() → creates CaseDiscussion record with references to patient data
5. Broadcasts via notification system (Commons schema)

**Genomics Data Pipeline:**
1. GenomicsController accepts uploaded variant files or patient ID
2. GenomicsService/ClinVarSyncService annotates variants via ClinVar API
3. Genomic variants stored in clinical.genomic_variants + evidence_updates
4. OncoKB service maps therapeutics (gene → drug interaction)
5. Frontend: GenomicsTab displays variants, actionable findings, treatment options
6. Abby AI (FastAPI) can analyze genomic context and provide briefing

**Admin Operations:**
1. Super-admin (admin@acumenus.net) has all 8 roles via Spatie RBAC
2. UserController (Admin) manages users: create, activate/deactivate, assign roles/permissions
3. All mutations logged to user_audit_logs with user_id, action, model, changes
4. SystemHealthController monitors service status: PHP health, DB connectivity, Redis health
5. AppSettingsController manages feature flags and integrations (AiProviderSetting)

## State Management

**Backend State:**
- Persisted: PostgreSQL (transactions, consistency)
- Cache: Redis (session tokens, computed results)
- Audit: user_audit_logs table (immutable event log)
- WebSocket sessions: In-memory, cleaned up on disconnect

**Frontend State:**
- Persisted: localStorage (Zustand persist middleware on authStore, profileStore)
- In-memory: Zustand (ui state, abby conversation history)
- Server-cached: TanStack Query (patients, cases, discussions, genomics data)
- UI state: React component useState for local forms, modals, tabs

## Key Abstractions

**ClinicalDataAdapter:**
- Purpose: Decouple business logic from data source (FHIR, OMOP, manual entry)
- Examples: `backend/app/Services/Adapters/ManualAdapter.php`, FHIR implementation pending
- Pattern: Strategy pattern — interface defines contract, implementations swap at runtime
- Used by: PatientService.getProfile(), searchPatients()

**ApiResponse Helper:**
- Purpose: Consistent JSON envelope for all API responses
- Examples: `backend/app/Http/Helpers/ApiResponse.php`
- Pattern: Static methods for success(), error(), paginated() responses
- Format: `{success: bool, message: string, data: mixed, errors?: mixed}`

**Form Requests:**
- Purpose: Centralized input validation and authorization
- Location: `backend/app/Http/Requests/`
- Pattern: Extend FormRequest, define rules() and authorize() methods
- Example: StoreDiscussionRequest validates message body, references, permissions

**Eloquent Models & Scopes:**
- Purpose: Encapsulate domain logic in models (relationships, query filters)
- Examples: ClinicalCase.scopeActive(), ClinicalCase.scopeForUser()
- Pattern: Query builder scopes return Builder for chainability

**Feature Modules (Frontend):**
- Purpose: Encapsulate feature code (pages, components, hooks, API, types)
- Structure: Each feature at `frontend/src/features/{feature}/` with api.ts, hooks/, components/, pages/, types/
- Pattern: Features import from each other and commons; commons provides cross-feature utilities

## Entry Points

**API Entry:**
- Location: `backend/routes/api.php`
- Triggers: HTTP requests to /api/*
- Responsibilities: Route definitions, middleware attachment (auth:sanctum, throttle, CORS), dependency injection
- Pattern: Route groups by feature (prefix), protected groups behind auth middleware

**Frontend Entry:**
- Location: `frontend/src/main.tsx`
- Triggers: Page load
- Responsibilities: Initialize React app, Zustand stores, TanStack Query client, providers
- Pattern: BrowserRouter wraps all routes; QueryClientProvider provides caching; ErrorBoundary catches React errors

**Auth Middleware:**
- Location: Laravel Sanctum middleware (built-in)
- Triggers: Routes under `middleware('auth:sanctum')` group
- Responsibilities: Validate token, inject authenticated user into request
- Pattern: Token validation via request()->user(), throws 401 if invalid

**Scheduler (Background Jobs):**
- Purpose: Automated tasks (variant syncs, evidence updates, session cleanup)
- Location: `backend/app/Console/Commands/`
- Examples: SyncClinVarCommand (ClinVar annotations), RefreshEvidenceCommand (therapeutics)
- Pattern: Scheduled via kernel.php or queued via Redis

## Error Handling

**Strategy:** Graceful degradation with user-facing messages, server-side logging

**Patterns:**
- **Backend:** Controllers catch exceptions, return ApiResponse::error() with safe message; logs full error context
- **Frontend:** API client interceptor catches 401 → logout + redirect to /login; Toast notifications for user feedback
- **Validation:** Form Requests return 422 with field-specific errors; frontend displays inline
- **Database:** Soft deletes (SoftDeletes trait) prevent data loss; foreign keys cascade appropriately
- **Async (Abby):** FastAPI error handling returns 400/500 with descriptive message; frontend shows fallback UI

## Cross-Cutting Concerns

**Logging:**
- Backend: Laravel Log facade → logs/laravel.log (json format)
- Frontend: Console logs + Sentry integration (if configured)
- AI: Python logging to ai/logs/

**Validation:**
- Backend: Form Requests with Laravel Validation rules; custom Rule classes in `app/Rules/`
- Frontend: Zod or runtime validation on API responses; form libraries (React Hook Form, Formik)
- AI: Pydantic models for request/response validation

**Authentication:**
- Sanctum tokens valid for API requests; single-token-per-device model
- must_change_password flag forces password change before full access
- Superuser account (admin@acumenus.net) never requires password change
- Role-based access control via Spatie: roles → permissions → controller policies

**Authorization:**
- Policies in `backend/app/Policies/` (ModelPolicy pattern)
- Gates in AuthServiceProvider for custom checks
- Frontend: useAuthStore.hasRole(), hasPermission() helpers
- Example: Only case creator or team members can view case details

**Rate Limiting:**
- Public routes (register, login): 3-5 requests/minute
- AI proxy endpoint: 30 requests/minute per user
- Built-in via Laravel throttle middleware

**Security Headers:**
- SecurityHeaders middleware applies CSP, HSTS, X-Frame-Options, X-Content-Type-Options
- Local dev: Allows unsafe-inline, webpack dev server; production: strict CSP
- Location: `backend/app/Http/Middleware/SecurityHeaders.php`

---

*Architecture analysis: 2026-03-24*
