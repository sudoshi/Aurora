# Coding Conventions

**Analysis Date:** 2026-03-24

## Naming Patterns

**Files:**
- **TypeScript/React:** PascalCase for components (e.g., `Modal.tsx`, `DataTable.tsx`), camelCase for utilities and hooks (e.g., `api-client.ts`, `useAbbyContext.ts`)
- **PHP:** PascalCase for classes (e.g., `AuthService.php`, `EventService.php`), camelCase for methods
- **Python:** snake_case for modules and functions (e.g., `knowledge_capture.py`, `get_session()`)
- **Features:** Feature directories use kebab-case (e.g., `patient-profile`, `case-discussion`)

**Functions:**
- **TypeScript:** camelCase throughout. Async functions commonly use `fetch*`, `get*`, `list*` naming (e.g., `getGenomicsStats()`, `listUploads()`)
- **React Components:** PascalCase (e.g., `function Modal()`). Hooks use `use*` prefix (e.g., `useAbbyContext()`, `useAuthStore()`)
- **PHP Services:** camelCase methods with clear intent (e.g., `register()`, `login()`, `changePassword()`)
- **Python:** snake_case with clear action verbs (e.g., `get_engine()`, `get_session()`)

**Variables:**
- **TypeScript:** camelCase for all variables. Boolean prefixes `is*` or `has*` (e.g., `isAuthenticated`, `hasRole()`)
- **PHP:** camelCase for properties and variables. Boolean properties with clear names (e.g., `must_change_password`, `is_active`)
- **React Stores:** State objects use camelCase (e.g., `token`, `user`, `isAuthenticated`)

**Types:**
- **TypeScript:** Interfaces use PascalCase (e.g., `interface User`, `interface AuthState`, `interface ModalProps`)
- **Generic type parameters:** Uppercase single letters (e.g., `<T>` in `DataTable<T>`)
- **API response types:** Descriptive PascalCase (e.g., `PaginatedResponse<T>`, `GenomicVariant`)

**Exports:**
- **Barrel files:** Used in feature modules (e.g., `types/index.ts` may re-export public types)
- **Default vs named exports:** Named exports preferred for utilities and services, default exports for pages

## Code Style

**Formatting:**
- **Tool:** No explicit linter/formatter config found — project uses TypeScript strict mode and Laravel conventions
- **Indentation:** 2 spaces (TypeScript/React) or language defaults
- **Line length:** No hard limit enforced; 80-100 character preference for readability
- **Semicolons:** Required in TypeScript/JavaScript (files use them consistently)
- **Trailing commas:** Used in multi-line objects/arrays

**Linting:**
- **Frontend:** ESLint v9 in `frontend/package.json` — strict mode enforced
- **TypeScript:** `tsconfig.json` enforces strict mode, `noUnusedLocals`, `noUnusedParameters`, `noFallthroughCasesInSwitch`
- **PHP:** Laravel Pint (code formatter) available via `require-dev`
- **Python:** Type hints used throughout (e.g., `Generator[Session, None, None]`)

## Import Organization

**Order (TypeScript/React):**
1. External libraries (React, routing, state, API clients)
2. Absolute imports (`@/lib/*`, `@/stores/*`)
3. Relative imports (local utilities, components)
4. Type imports (separate from value imports when needed)

**Example:**
```typescript
import { useEffect } from "react";
import { useLocation } from "react-router-dom";
import { useAbbyStore } from "@/stores/abbyStore";
import apiClient from "@/lib/api-client";
import { cn } from "@/lib/utils";
```

**Path Aliases:**
- Frontend: `@/*` maps to `src/*` (defined in `tsconfig.json`)
- No aliases in backend (standard Laravel structure)

**PHP:**
- Namespace imports at top (e.g., `use App\Models\User;`)
- Group by category: framework classes, then models, then services

## Error Handling

**Frontend (TypeScript/React):**
- API errors caught at request interceptor level (`lib/api-client.ts`)
- 401 errors trigger automatic logout and redirect to `/login`
- All promises wrapped with error handler: `Promise.reject(error as Error)`
- Component-level error handling via `ErrorBoundary` component

**Backend (PHP/Laravel):**
- Exceptions thrown with explicit status codes: `throw new \RuntimeException('message', 401)`
- Service methods throw `\RuntimeException` for business logic failures
- Controllers return `ApiResponse::error()` for HTTP responses
- Validation errors return 422 with detailed `$errors` payload
- Password verification uses unified error message: "The provided credentials do not match our records" (prevents email enumeration)

**Python (FastAPI):**
- All functions include return type hints
- Context managers used for resource management (`@contextmanager` decorator)
- Logging via `logger = logging.getLogger(__name__)`

## Logging

**Framework:**
- **Frontend:** No centralized logging framework — errors handled via error boundary
- **Backend:** Laravel's `Illuminate\Support\Facades\Log` (used in `AuthService::sendTempPasswordEmail()`)
- **Python:** Python's standard `logging` module with module-level loggers

**Patterns:**
- Backend logs errors during non-fatal operations (email sending)
- No request/response logging in sensitive endpoints (auth)
- Debug info uses Laravel's `Log::debug()` for non-critical flows

## Comments

**When to Comment:**
- **Docblocks:** PHP methods include PHPDoc with `@param` and `@return` type hints
- **Function signatures:** TypeScript functions include parameter type hints and return types
- **Complex logic:** Rare — code is self-documenting through clear naming and structure

**JSDoc/TSDoc:**
- Not heavily used — TypeScript inference handles most documentation
- PHP uses PHPDoc format: `/** @param array{name: string, email: string} $data */`

**Example (PHP):**
```php
/**
 * Register a new user with a temporary password sent via email.
 *
 * @param  array{name: string, email: string, phone?: string|null}  $data
 * @return array{message: string}
 */
public function register(array $data): array
```

## Function Design

**Size:**
- **Target:** 50-100 lines for complex functions, 10-30 lines for most utilities
- **UI components:** Usually 50-80 lines including JSX
- **Example:** `Modal.tsx` is 82 lines including formatting

**Parameters:**
- **Type safety:** All parameters have type annotations (TypeScript interfaces, PHP type hints)
- **Destructuring:** Used for options objects (e.g., `{ open, onClose, title }` in `Modal`)
- **Variadic args:** Not common; object parameters preferred

**Return Values:**
- **Explicit types:** All functions declare return type
- **Consistency:** Services return objects/arrays; components return JSX
- **Nullable:** Explicitly typed (e.g., `Promise<GenomicUpload>`, `null` handled separately)

## Module Design

**Exports:**
- **Services:** Export class definition with no default export (e.g., `export class AuthService`)
- **Utilities:** Export named functions (e.g., `export function cn(...)`)
- **Stores:** Export store factory (e.g., `export const useAuthStore = create<AuthState>(...)`)
- **Components:** Default export for route-lazy-loaded pages, named exports for reusable UI components

**Barrel Files:**
- Feature directories may have index files but not heavily used
- Direct imports preferred: `import { useAbbyContext } from "@/hooks/useAbbyContext"`

**Single Responsibility:**
- Files stay focused: `useAuthStore.ts` handles auth state only
- Large files broken down: `genomicsApi.ts` separates API by concern (Stats, Uploads, Variants, etc.)

## State Management (Frontend)

**Zustand Stores:**
- Store definition: `create<StateInterface>()(persist((set, get) => ({ ... }), { name: "store-key" }))`
- Actions use immutability: `updateUser: (partial) => { const current = get().user; set({ user: { ...current, ...partial } }); }`
- Selectors via hook: `const token = useAuthStore((s) => s.token)`
- Persistence via `persist` middleware with named key

**TanStack Query:**
- All API calls go through hooks powered by TanStack Query
- No direct API calls in components (use hooks instead)
- Devtools available for debugging

## Database & ORM

**Laravel Eloquent:**
- Model relationships defined via methods (e.g., `discussions()`, `followUps()`)
- Query scopes used for reusable filters (e.g., `->pending()`, `->unresolved()`)
- Model `casts()` method defines attribute types (`boolean`, `datetime`, `hashed`)
- Fillable/guarded used for mass assignment protection

**Python SQLAlchemy:**
- Type hints on all return values and parameters
- Context managers for session management
- Schema-qualified metadata: `MetaData(schema="vocab")`

## API Response Format

**Standard envelope (all endpoints):**
```json
{
  "success": true,
  "message": "Success",
  "data": { /* payload */ }
}
```

**Error envelope:**
```json
{
  "success": false,
  "message": "Error description",
  "errors": { /* optional validation details */ }
}
```

**Paginated:**
```json
{
  "success": true,
  "message": "Success",
  "data": [ /* items */ ],
  "meta": {
    "total": 42,
    "page": 1,
    "per_page": 15,
    "last_page": 3
  }
}
```

Helper: `ApiResponse::success()`, `ApiResponse::error()`, `ApiResponse::paginated()`

## Immutability

**Critical requirement (enforced throughout):**
- TypeScript: Object updates use spread operator (`{ ...current, ...partial }`)
- No mutation of state: `set({ user: { ...current, ...partial } })` not `current.user = value`
- React: All state updates create new objects/arrays
- PHP: Laravel models use `create()` and `update()` methods, not direct assignment

## Security Patterns

**Authentication:**
- Sanctum token-based auth via Bearer token in Authorization header
- Temp password flow: register sends no password, email triggers Resend with temp creds
- Forced password change on first login via `must_change_password` flag
- 401 errors in API responses trigger automatic logout

**Input Validation:**
- Frontend: Form validation via HTML5 + error handling in interceptors
- Backend: Form Request classes validate input with detailed error messages
- Email enumeration prevention: registration returns same message for existing/new emails
- Password requirements: min 8 chars, bcrypt 12 rounds (configurable in testing)

**Authorization:**
- Role-based access control via Spatie permissions
- User model: `hasRole()`, `hasPermission()`, `isAdmin()`, `isSuperAdmin()` helpers
- Superuser check: `isSuperuser()` returns true only for `admin@acumenus.net`

---

*Convention analysis: 2026-03-24*
