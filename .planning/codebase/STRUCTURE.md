# Codebase Structure

**Analysis Date:** 2026-03-24

## Directory Layout

```
Aurora/                                 # Monorepo root
├── backend/                            # Laravel API (PHP 8.4)
│   ├── app/
│   │   ├── Console/Commands/          # CLI commands (SyncClinVar, RefreshEvidence)
│   │   ├── Contracts/                 # Interfaces (ClinicalDataAdapter)
│   │   ├── Http/
│   │   │   ├── Controllers/           # 39 controllers (Auth, Patient, Case, etc.)
│   │   │   ├── Helpers/               # ApiResponse helper
│   │   │   ├── Middleware/            # SecurityHeaders, RecordUserActivity
│   │   │   └── Requests/              # Form Request validation classes
│   │   ├── Models/                    # Eloquent models (User, ClinicalCase, etc.)
│   │   │   └── Clinical/              # Clinical data models (Patient, Condition, Medication, etc.)
│   │   ├── Providers/                 # Service providers (AppServiceProvider, RouteServiceProvider)
│   │   ├── Rules/                     # Custom validation rules
│   │   └── Services/                  # Business logic services
│   │       ├── Adapters/              # ClinicalDataAdapter implementations
│   │       └── Genomics/              # ClinVarAnnotationService, OncoKbService
│   ├── bootstrap/                     # Laravel bootstrap files
│   ├── config/                        # Laravel config (app, database, cache, mail, auth)
│   ├── database/
│   │   ├── factories/                 # Model factories for seeding
│   │   ├── migrations/                # 31 migrations (schemas, tables, indexes)
│   │   └── seeders/                   # Database seeders (DatabaseSeeder, RoleSeeder)
│   ├── public/                        # Web root (nginx serves from here)
│   │   └── build/                     # Frontend build output (Vite dist copied here)
│   ├── resources/                     # Laravel Blade views (minimal, SPA uses React)
│   ├── routes/
│   │   └── api.php                    # All API route definitions
│   ├── storage/                       # Logs, cache, file uploads
│   ├── tests/
│   │   ├── Feature/                   # API endpoint tests (Pest)
│   │   └── Unit/                      # Unit tests
│   ├── .env.example                   # Environment template
│   ├── artisan                        # Laravel CLI
│   ├── composer.json                  # PHP dependencies
│   └── phpunit.xml                    # Test configuration
│
├── frontend/                          # React SPA (TypeScript, Vite, Tailwind)
│   ├── src/
│   │   ├── components/
│   │   │   ├── layout/                # Header, Sidebar, CommandPalette, AbbyPanel
│   │   │   ├── layouts/               # DashboardLayout (wraps protected routes)
│   │   │   ├── navigation/            # TopNavigation, breadcrumbs
│   │   │   ├── ui/                    # Reusable UI (Button, Modal, DataTable, Toast, etc.)
│   │   │   └── ErrorBoundary.tsx      # React error boundary
│   │   ├── config/                    # Frontend config (constants, themes)
│   │   ├── features/                  # Feature modules (12 features)
│   │   │   ├── abby-ai/              # AI conversation interface (api, components, hooks, types)
│   │   │   ├── administration/       # Admin user, role, AI provider management
│   │   │   ├── auth/                 # Login, Register, ChangePasswordModal
│   │   │   ├── cases/                # Case list, detail, templates
│   │   │   ├── collaboration/        # Sessions (video/whiteboard), team collaboration
│   │   │   ├── commons/              # Channels, messaging, wiki, announcements, notifications
│   │   │   ├── copilot/              # AI copilot for clinical decision support
│   │   │   ├── dashboard/            # Main dashboard with stats
│   │   │   ├── decisions/            # Clinical decisions, voting, followups
│   │   │   ├── genomics/             # Genomic variants, tumor board, evidence
│   │   │   ├── imaging/              # OHIF medical imaging viewer integration
│   │   │   ├── patient-profile/      # Patient demographics, timeline, labs, notes
│   │   │   └── settings/             # User profile, notification preferences
│   │   ├── hooks/                    # Global hooks (useAbbyContext, etc.)
│   │   ├── lib/
│   │   │   ├── api-client.ts        # Axios instance with Sanctum token injection
│   │   │   ├── query-client.ts      # TanStack Query configuration
│   │   │   └── utils.ts             # Utility functions (cn, format, etc.)
│   │   ├── stores/                  # Zustand stores (authStore, profileStore, uiStore, abbyStore)
│   │   ├── styles/                  # Global styles, Tailwind config
│   │   ├── types/                   # Shared TypeScript types
│   │   ├── App.tsx                  # Main app component (routes, providers)
│   │   ├── main.tsx                 # Entry point
│   │   └── vite-env.d.ts            # Vite type definitions
│   ├── public/                       # Static assets (images, fonts)
│   ├── vite.config.ts               # Vite configuration
│   ├── tsconfig.json                # TypeScript configuration (strict mode)
│   ├── tailwind.config.ts           # Tailwind CSS config (design tokens)
│   ├── package.json                 # npm dependencies
│   └── index.html                   # SPA HTML template
│
├── ai/                              # Python FastAPI service (AI reasoning)
│   ├── app/
│   │   ├── agency/                  # Agency pattern (multi-agent coordination)
│   │   ├── institutional/           # Institution-level reasoning
│   │   ├── knowledge/               # Knowledge base integration
│   │   ├── memory/                  # Session memory management
│   │   ├── models/                  # Pydantic models for requests/responses
│   │   ├── routers/
│   │   │   └── abby.py             # Abby AI endpoint (/abby/analyze, /abby/chat, /abby/chat/stream)
│   │   ├── routing/                 # Request routing
│   │   └── services/                # MedGemma client, external API integrations
│   ├── tests/                       # pytest tests
│   ├── main.py                      # FastAPI app entry
│   ├── config.py                    # Configuration (Ollama URL, model selection)
│   ├── requirements.txt              # Python dependencies
│   └── venv/                        # Virtual environment
│
├── e2e/                             # Playwright end-to-end tests
│   └── tests/
│       ├── auth.spec.ts            # Registration, login, password change flows
│       ├── patient-profile.spec.ts # Patient data retrieval and display
│       ├── case-lifecycle.spec.ts  # Case creation, team, discussions
│       ├── session-lifecycle.spec.ts # Session collaboration
│       ├── commons.spec.ts         # Messaging, channels, wiki
│       ├── imaging.spec.ts         # OHIF viewer integration
│       ├── copilot.spec.ts         # AI copilot interactions
│       └── admin.spec.ts           # Admin operations
│
├── federation/                      # Federation layer (opt-in SSO)
│   └── tests/                       # Federation integration tests
│
├── docker/                          # Docker configurations
│   ├── nginx/                       # Nginx config
│   ├── php/                         # PHP-FPM Dockerfile
│   ├── ai/                          # Python AI service Dockerfile
│   └── ohif/                        # OHIF viewer Dockerfile
│
├── docs/                            # Documentation
│   ├── api/                         # API documentation
│   ├── deployment/                  # Deployment guides
│   ├── federation/                  # Federation specs
│   ├── notes/                       # Research, market notes
│   ├── plans/                       # Implementation plans (v2 overhaul design)
│   └── superpowers/                 # Advanced features docs
│
├── dicom/                           # DICOM data (phase downloads, logs)
├── docker-compose.yml               # Docker Compose services
├── deploy.sh                        # Production deployment script
├── Makefile                         # Development shortcuts
├── .env.example                     # Environment template
├── .gitignore                       # Git ignore patterns
└── README.md                        # Project overview
```

## Directory Purposes

**backend/app/Http/Controllers/:**
- Purpose: HTTP request handlers (39 controllers)
- Contains: AuthController, PatientController, CaseController, AbbyController, GenomicsController, Admin/* (UserController, RoleController, AiProviderController, SystemHealthController), Commons/* (ChannelController, MessageController, DirectMessageController, WikiController, NotificationController, etc.)
- Key files: AuthController.php (register, login, changePassword), PatientController.php (index, search, profile)
- Patterns: Dependency injection, return typed JsonResponse, delegate validation to Form Requests

**backend/app/Http/Requests/:**
- Purpose: Input validation and authorization
- Contains: Form Request classes (one per controller action or shared across related actions)
- Pattern: Extend FormRequest, define rules() for validation, authorize() for permission checks
- Used by: Controllers call $request->validate() to trigger automatic validation

**backend/app/Models/:**
- Purpose: Data representation
- Contains: 30+ Eloquent models (User.php, ClinicalCase.php, CaseDiscussion.php, etc.)
- Relationships: BelongsTo, HasMany, HasManyThrough defined in model methods
- Scopes: Query builder helpers (scopeActive, scopeForUser, etc.)
- Traits: HasRoles (Spatie), SoftDeletes, HasFactory, Notifiable

**backend/app/Models/Clinical/:**
- Purpose: Clinical domain models
- Contains: 22 clinical models (ClinicalPatient, Condition, Medication, Procedure, ImagingStudy, GenomicVariant, etc.)
- Schema: clinical.* tables (clinical.patients, clinical.conditions, clinical.medications, etc.)
- Pattern: Each model represents a clinical entity with relationships and validation

**backend/app/Services/:**
- Purpose: Business logic encapsulation
- Contains: 9 services (PatientService, CaseService, AuthService, CaseDiscussionService, EventService, RadiogenomicsService, and Genomics/*Service)
- Pattern: Services accept models or primitives, return models or arrays; methods are single-responsibility

**backend/app/Services/Adapters/:**
- Purpose: Data source abstraction
- Contains: ManualAdapter (default), FHIR adapter (pending), OMOP adapter (pending)
- Implements: ClinicalDataAdapter interface (defines contract)
- Pattern: Services depend on interface, implementations swap at runtime

**backend/database/migrations/:**
- Purpose: Schema versioning
- Contains: 31 migrations (schemas, tables, foreign keys, indexes)
- Pattern: Timestamped filenames for ordering; each migration is idempotent
- Schemas: app (app-level), clinical (clinical data), commons (collaboration)

**frontend/src/features/:**
- Purpose: Feature encapsulation
- Contains: 12 features (auth, patient-profile, cases, collaboration, commons, genomics, imaging, etc.)
- Structure: Each feature has api.ts, hooks/, components/, pages/, types/
- Pattern: Features are self-contained; cross-feature imports go through commons or direct import

**frontend/src/features/*/api.ts:**
- Purpose: Feature-specific API functions and TanStack Query hooks
- Pattern: Export typed async functions (fetchX, createX, updateX), then export useQuery/useMutation hooks
- Example: `frontend/src/features/commons/api.ts` exports useChannels(), useMessages(), useSendMessage()

**frontend/src/features/*/hooks/:**
- Purpose: Custom React hooks for feature logic
- Pattern: Each hook wraps API calls and state management
- Example: `usePatientProfile()` combines useQuery + local state + TanStack Query

**frontend/src/stores/:**
- Purpose: Global state management (Zustand)
- Contains: authStore (token, user, roles, permissions), profileStore (selected patient), uiStore (sidebar state), abbyStore (AI conversation)
- Pattern: Zustand create() with persist middleware for localStorage persistence

**frontend/src/lib/:**
- Purpose: Shared utilities and infrastructure
- api-client.ts: Axios instance with Sanctum token injection and 401 interceptor
- query-client.ts: TanStack Query client configuration (cache time, retry logic)
- utils.ts: Helper functions (cn for class composition, format utilities, etc.)

**ai/app/routers/:**
- Purpose: FastAPI endpoint definitions
- Contains: abby.py router with /analyze (case analysis), /chat (conversational), /chat/stream (SSE streaming)
- Pattern: Pydantic validation, async handlers, streaming responses

**ai/app/services/:**
- Purpose: External API clients and business logic
- Contains: MedGemma client (Ollama), external API integrations (ClinVar, OncoKB)
- Pattern: Async/await, error handling with retries

**e2e/tests/:**
- Purpose: End-to-end test scenarios
- Contains: Playwright tests covering all major flows (auth, patient profile, cases, collaboration, imaging, admin)
- Pattern: Page Object Model (optional), fixtures for user creation

## Key File Locations

**Entry Points:**
- `backend/routes/api.php`: All API route definitions (public, auth-protected groups)
- `frontend/src/App.tsx`: Main app component (routes, providers, lazy loading)
- `frontend/src/main.tsx`: React mount point
- `ai/main.py`: FastAPI application factory

**Configuration:**
- `backend/.env`: Environment variables (DB, Resend API key, Redis, etc.)
- `frontend/vite.config.ts`: Vite build and dev config (API proxy, asset handling)
- `backend/config/`: Laravel config files (app, database, cache, mail, sanctum)
- `ai/config.py`: FastAPI config (Ollama URL, model selection)

**Core Logic:**
- `backend/app/Services/PatientService.php`: Patient profile retrieval via ClinicalDataAdapter
- `backend/app/Services/AuthService.php`: Auth logic (register, login, password change, token management)
- `backend/app/Services/CaseService.php`: Case creation, team member management
- `frontend/src/features/patient-profile/hooks/useProfiles.ts`: Patient data hooks
- `frontend/src/features/commons/api.ts`: Messaging, channels, notifications

**Authentication & Authorization:**
- `backend/app/Http/Controllers/AuthController.php`: Auth endpoints
- `backend/app/Models/User.php`: User model with roles/permissions
- `frontend/src/stores/authStore.ts`: Client-side auth state
- `backend/app/Http/Middleware/SecurityHeaders.php`: CSP and security header injection

**Testing:**
- `backend/tests/Feature/`: API endpoint tests
- `backend/tests/Unit/`: Unit tests
- `e2e/tests/`: Playwright end-to-end tests
- `ai/tests/`: pytest tests

## Naming Conventions

**Files:**
- Controllers: PascalCase.php (e.g., PatientController.php, CaseDiscussionController.php)
- Models: PascalCase.php (e.g., ClinicalCase.php, ClinicalPatient.php)
- Services: PascalCase.php with 'Service' suffix (e.g., PatientService.php, AuthService.php)
- Form Requests: PascalCase ending in 'Request' (e.g., StoreDiscussionRequest.php)
- Frontend components: PascalCase.tsx (e.g., PatientDemographicsCard.tsx)
- Frontend hooks: use{Feature}.ts (e.g., usePatientProfile.ts, useAbbyContext.ts)
- Frontend pages: PascalCase ending in 'Page' (e.g., PatientProfilePage.tsx)
- API files: api.ts (e.g., frontend/src/features/commons/api.ts)
- Types: PascalCase.ts or types.ts in feature directories

**Directories:**
- Backend feature domains: lowercase (Commons/Controllers, Genomics/Services)
- Frontend features: kebab-case folders, PascalCase for nested components (e.g., patient-profile, CommonsPage)
- Models by domain: models/Clinical/, models/Commons/ subdirectories
- Migration timestamps: 2026_MM_DD_NNNNNN_description pattern

**Interfaces/Contracts:**
- Backend: PascalCase ending in Interface or no suffix (e.g., ClinicalDataAdapter)
- Location: `backend/app/Contracts/`

**Classes/Types:**
- Frontend: PascalCase (e.g., PatientProfileResponse, GenomicVariant)
- Backend: PascalCase (e.g., ClinicalFinding, PatientDemographics)

## Where to Add New Code

**New Feature (e.g., "Radiology Reports"):**
- Backend controller: `backend/app/Http/Controllers/RadiologyController.php`
- Backend model: `backend/app/Models/RadiologyReport.php`
- Backend service: `backend/app/Services/RadiologyService.php`
- Frontend feature: `frontend/src/features/radiology/` with pages/, components/, api.ts, hooks/, types/
- Routes: Add endpoints to `backend/routes/api.php` under new feature group
- Tests: `backend/tests/Feature/RadiologyControllerTest.php`, `e2e/tests/radiology.spec.ts`

**New Component (e.g., "PatientTimelineEvent"):**
- Implementation: `frontend/src/features/patient-profile/components/PatientTimelineEvent.tsx`
- Types: Add to `frontend/src/features/patient-profile/types/index.ts`
- Import in parent: `PatientTimeline.tsx` imports and uses it

**New Utility/Hook (e.g., "useClinicalDataFilter"):**
- Shared hook: `frontend/src/hooks/useClinicalDataFilter.ts`
- Used by: Any feature that needs filtering logic
- Pattern: Hook returns filtered data + setState function

**New Backend Service Method:**
- Add to existing service or create new service file
- Follow pattern: Accept models/primitives, return models/arrays
- Inject dependencies via constructor
- Use domain models for return types

**New Database Table (e.g., "RadiologyReports"):**
- Create migration: `backend/database/migrations/2026_MM_DD_NNNNNN_create_radiology_reports_table.php`
- Create model: `backend/app/Models/RadiologyReport.php`
- Define relationships in model
- Add to appropriate schema (app, clinical, or commons)

**New API Endpoint:**
- Add route to `backend/routes/api.php` (group by feature)
- Create or extend controller in `backend/app/Http/Controllers/`
- Create Form Request for validation in `backend/app/Http/Requests/`
- Implement service logic in `backend/app/Services/`
- Return via ApiResponse helper for consistency

**New Frontend API Hook:**
- Add function to feature's `api.ts` file
- Create TanStack Query useQuery or useMutation wrapper
- Export typed hook with cache keys
- Use in components via custom hooks

**Frontend Page Route:**
- Add lazy-loaded import to `frontend/src/App.tsx`
- Add Route to appropriate group (public, protected, admin)
- Implement page component at `frontend/src/features/{feature}/pages/{Feature}Page.tsx`

## Special Directories

**backend/storage/logs/:**
- Purpose: Laravel application logs
- Generated: Yes (on each log write)
- Committed: No (.gitignore excludes)
- Files: laravel.log (main application log), test logs for test runs

**backend/bootstrap/cache/:**
- Purpose: Framework bootstrap cache (config, routes, services)
- Generated: Yes (php artisan config:cache, route:cache)
- Committed: No
- Refresh: Run cache:clear after config changes

**frontend/node_modules/:**
- Purpose: npm package dependencies
- Generated: Yes (npm install)
- Committed: No (.gitignore)
- Lockfile: package-lock.json (committed)

**frontend/dist/:**
- Purpose: Vite production build output
- Generated: Yes (npm run build)
- Committed: No
- Deploy: Copied to `backend/public/build/` for nginx to serve

**backend/public/build/:**
- Purpose: Frontend built assets (after npm run build + copy to backend/public)
- Generated: Yes (from frontend/dist after build)
- Committed: No
- Served: By nginx at /* routes

**backend/storage/framework/**:
- Purpose: Laravel cache, sessions, compiled views
- Generated: Yes (runtime)
- Committed: No
- Cleanup: storage:clear artisan command

**dicom/:**
- Purpose: Medical imaging data (test fixtures, downloaded studies)
- Generated: Yes (phase imports)
- Committed: No (.gitignore)
- Source: TCIA manifests reference external imaging data

---

*Structure analysis: 2026-03-24*
