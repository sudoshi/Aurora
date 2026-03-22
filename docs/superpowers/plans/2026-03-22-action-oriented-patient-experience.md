# Action-Oriented Patient Experience Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Transform Aurora's patient views from passive data browsers into an action-oriented clinical collaboration surface with a Clinical Briefing dashboard, inline actions on all data views, a context-sensitive collaboration panel, and enhanced session agendas.

**Architecture:** Four-phase additive redesign. Phase 1 adds backend schema + Briefing UI. Phase 2 adds inline action menus to data views. Phase 3 adds the collaboration panel. Phase 4 enhances session/case pages. Each phase is independently deployable.

**Tech Stack:** Laravel 10 / PHP 8.1+ (backend), React 19 / TypeScript strict / Tailwind 4 (frontend), TanStack Query 5 (data fetching), Zustand 5 (state), Vitest + Testing Library (tests), PostgreSQL 16 (database).

**Spec:** `docs/superpowers/specs/2026-03-22-action-oriented-patient-experience-design.md`

---

## File Map

### New Files (Backend)

| File | Purpose |
|------|---------|
| `backend/database/migrations/2026_03_22_200001_create_patient_flags_table.php` | PatientFlag schema |
| `backend/database/migrations/2026_03_22_200002_create_patient_tasks_table.php` | PatientTask schema |
| `backend/database/migrations/2026_03_22_200003_add_patient_anchoring_columns.php` | Add patient_id + record_refs to decisions, discussions, annotations, follow_ups |
| `backend/app/Models/PatientFlag.php` | PatientFlag Eloquent model |
| `backend/app/Models/PatientTask.php` | PatientTask Eloquent model |
| `backend/app/Http/Controllers/PatientFlagController.php` | Flag CRUD |
| `backend/app/Http/Controllers/PatientTaskController.php` | Task CRUD |
| `backend/app/Http/Controllers/PatientCollaborationController.php` | Collaboration aggregate endpoint |
| `backend/app/Http/Requests/StorePatientFlagRequest.php` | Flag validation |
| `backend/app/Http/Requests/StorePatientTaskRequest.php` | Task validation |
| `backend/app/Rules/ValidRecordRef.php` | RecordRef format + domain validation rule |

### New Files (Frontend)

| File | Purpose |
|------|---------|
| `frontend/src/features/patient-profile/types/collaboration.ts` | PatientFlag, PatientTask, CollaborationData types |
| `frontend/src/features/patient-profile/api/collaborationApi.ts` | Flag/task/collaboration API calls |
| `frontend/src/features/patient-profile/hooks/useCollaboration.ts` | TanStack Query hooks for flags, tasks, collaboration |
| `frontend/src/features/patient-profile/components/PatientBriefing.tsx` | Four-quadrant briefing dashboard |
| `frontend/src/features/patient-profile/components/ActiveProblemsList.tsx` | Active conditions + treatments |
| `frontend/src/features/patient-profile/components/FlaggedFindings.tsx` | Flagged items with severity |
| `frontend/src/features/patient-profile/components/PendingActions.tsx` | Follow-ups + standalone tasks |
| `frontend/src/features/patient-profile/components/RecentDecisions.tsx` | Decisions with vote summary |
| `frontend/src/features/patient-profile/components/InlineActionMenu.tsx` | Three-dot + right-click context menu |
| `frontend/src/features/patient-profile/components/SelectActToolbar.tsx` | Floating toolbar for batch actions |
| `frontend/src/features/patient-profile/components/CollaborationPanel.tsx` | Slide-out right panel shell |
| `frontend/src/features/patient-profile/components/PanelDiscussionTab.tsx` | Filtered discussions |
| `frontend/src/features/patient-profile/components/PanelTasksTab.tsx` | Filtered tasks + follow-ups |
| `frontend/src/features/patient-profile/components/PanelFlagsTab.tsx` | Filtered flags |
| `frontend/src/features/patient-profile/components/PanelDecisionsTab.tsx` | Filtered decisions |
| `frontend/src/features/collaboration/components/SessionAgenda.tsx` | Multi-case ordered agenda |
| `frontend/src/features/collaboration/components/SessionDecisionLog.tsx` | Per-case decision capture |

### Modified Files

| File | Changes |
|------|---------|
| `backend/routes/api.php` | Add flag, task, collaboration routes |
| `backend/app/Models/Decision.php` | Add patient_id, record_refs, patient() relationship |
| `backend/app/Models/CaseDiscussion.php` | Add domain, record_ref, patient_id, patient() relationship |
| `backend/app/Models/CaseAnnotation.php` | Add patient_id, patient() relationship |
| `backend/app/Models/FollowUp.php` | Add patient_id, patient() relationship |
| `backend/app/Models/Clinical/ClinicalPatient.php` | Add flags(), tasks(), decisions() relationships |
| `frontend/src/features/patient-profile/pages/PatientProfilePage.tsx` | Add Briefing tab (default), CollaborationPanel, remove Eras |
| `frontend/src/features/patient-profile/components/PatientDemographicsCard.tsx` | Compact to single bar |
| `frontend/src/features/patient-profile/components/PatientGenomicsTab.tsx` | Add selection + inline actions |
| `frontend/src/features/patient-profile/components/PatientLabPanel.tsx` | Add selection + inline actions |
| `frontend/src/features/patient-profile/components/PatientNotesTab.tsx` | Add inline actions |
| `frontend/src/features/patient-profile/components/PatientVisitView.tsx` | Add inline actions |
| `frontend/src/features/patient-profile/components/PatientImagingTab.tsx` | Add inline actions |

---

## Phase 1: Schema Extensions + Briefing

### Task 1: Create PatientFlag migration and model

**Files:**
- Create: `backend/database/migrations/2026_03_22_200001_create_patient_flags_table.php`
- Create: `backend/app/Models/PatientFlag.php`

- [ ] **Step 1: Create migration**

```php
<?php
// backend/database/migrations/2026_03_22_200001_create_patient_flags_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app.patient_flags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('flagged_by');
            $table->string('domain'); // condition, medication, procedure, measurement, observation, genomic, imaging, general
            $table->string('record_ref'); // e.g., "genomic:42"
            $table->string('severity')->default('attention'); // critical, attention, informational
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('clinical.patients')->onDelete('cascade');
            $table->foreign('flagged_by')->references('id')->on('app.users');
            $table->foreign('resolved_by')->references('id')->on('app.users');

            $table->index('patient_id');
            $table->index(['patient_id', 'domain']);
        });

        // Partial index for unresolved flags
        DB::statement('CREATE INDEX idx_patient_flags_unresolved ON app.patient_flags(patient_id) WHERE resolved_at IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('app.patient_flags');
    }
};
```

- [ ] **Step 2: Create PatientFlag model**

```php
<?php
// backend/app/Models/PatientFlag.php

namespace App\Models;

use App\Models\Clinical\ClinicalPatient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientFlag extends Model
{
    use HasFactory;

    protected $table = 'app.patient_flags';

    protected $fillable = [
        'patient_id',
        'flagged_by',
        'domain',
        'record_ref',
        'severity',
        'title',
        'description',
        'resolved_at',
        'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicalPatient::class, 'patient_id');
    }

    public function flagger(): BelongsTo
    {
        return $this->belongsTo(User::class, 'flagged_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopeUnresolved($query)
    {
        return $query->whereNull('resolved_at');
    }

    public function scopeForDomain($query, string $domain)
    {
        return $query->where('domain', $domain);
    }
}
```

- [ ] **Step 3: Run migration**

Run: `cd /home/smudoshi/Github/Aurora/backend && php artisan migrate`
Expected: Migration runs successfully, `app.patient_flags` table created.

- [ ] **Step 4: Commit**

```bash
git add backend/database/migrations/2026_03_22_200001_create_patient_flags_table.php backend/app/Models/PatientFlag.php
git commit -m "feat: add PatientFlag model and migration"
```

---

### Task 2: Create PatientTask migration and model

**Files:**
- Create: `backend/database/migrations/2026_03_22_200002_create_patient_tasks_table.php`
- Create: `backend/app/Models/PatientTask.php`

- [ ] **Step 1: Create migration**

```php
<?php
// backend/database/migrations/2026_03_22_200002_create_patient_tasks_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app.patient_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->string('domain')->nullable();
            $table->string('record_ref')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->string('status')->default('pending'); // pending, in_progress, completed, cancelled
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('clinical.patients')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('app.users');
            $table->foreign('assigned_to')->references('id')->on('app.users');
            $table->foreign('completed_by')->references('id')->on('app.users');

            $table->index('patient_id');
            $table->index(['patient_id', 'domain']);
        });

        DB::statement("CREATE INDEX idx_patient_tasks_assigned ON app.patient_tasks(assigned_to) WHERE status IN ('pending', 'in_progress')");
    }

    public function down(): void
    {
        Schema::dropIfExists('app.patient_tasks');
    }
};
```

- [ ] **Step 2: Create PatientTask model**

```php
<?php
// backend/app/Models/PatientTask.php

namespace App\Models;

use App\Models\Clinical\ClinicalPatient;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientTask extends Model
{
    use HasFactory;

    protected $table = 'app.patient_tasks';

    protected $fillable = [
        'patient_id',
        'created_by',
        'assigned_to',
        'domain',
        'record_ref',
        'title',
        'description',
        'due_date',
        'priority',
        'status',
        'completed_at',
        'completed_by',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────

    public function patient(): BelongsTo
    {
        return $this->belongsTo(ClinicalPatient::class, 'patient_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    // ── Scopes ───────────────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeForDomain($query, string $domain)
    {
        return $query->where('domain', $domain);
    }
}
```

- [ ] **Step 3: Run migration**

Run: `cd /home/smudoshi/Github/Aurora/backend && php artisan migrate`
Expected: Migration runs successfully, `app.patient_tasks` table created.

- [ ] **Step 4: Commit**

```bash
git add backend/database/migrations/2026_03_22_200002_create_patient_tasks_table.php backend/app/Models/PatientTask.php
git commit -m "feat: add PatientTask model and migration"
```

---

### Task 3: Add patient anchoring columns to existing tables

**Files:**
- Create: `backend/database/migrations/2026_03_22_200003_add_patient_anchoring_columns.php`
- Modify: `backend/app/Models/Decision.php`
- Modify: `backend/app/Models/CaseDiscussion.php`
- Modify: `backend/app/Models/CaseAnnotation.php`
- Modify: `backend/app/Models/FollowUp.php`
- Modify: `backend/app/Models/Clinical/ClinicalPatient.php`

- [ ] **Step 1: Create migration with backfill**

```php
<?php
// backend/database/migrations/2026_03_22_200003_add_patient_anchoring_columns.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Decisions: add patient_id + record_refs
        Schema::table('app.decisions', function (Blueprint $table) {
            $table->unsignedBigInteger('patient_id')->nullable()->after('session_id');
            $table->jsonb('record_refs')->nullable()->after('urgency');

            $table->foreign('patient_id')->references('id')->on('clinical.patients');
            $table->index('patient_id');
        });

        // Backfill: decisions.patient_id from decisions.case_id → cases.patient_id
        DB::statement('
            UPDATE app.decisions d
            SET patient_id = c.patient_id
            FROM app.cases c
            WHERE d.case_id = c.id AND c.patient_id IS NOT NULL
        ');

        // 2. Case discussions: add domain, record_ref, patient_id
        Schema::table('app.case_discussions', function (Blueprint $table) {
            $table->string('domain')->nullable()->after('content');
            $table->string('record_ref')->nullable()->after('domain');
            $table->unsignedBigInteger('patient_id')->nullable()->after('record_ref');

            $table->foreign('patient_id')->references('id')->on('clinical.patients');
            $table->index(['patient_id', 'domain']);
        });

        // Backfill: case_discussions.patient_id from case_id → cases.patient_id
        DB::statement('
            UPDATE app.case_discussions d
            SET patient_id = c.patient_id
            FROM app.cases c
            WHERE d.case_id = c.id AND c.patient_id IS NOT NULL
        ');

        // 3. Case annotations: add patient_id
        Schema::table('app.case_annotations', function (Blueprint $table) {
            $table->unsignedBigInteger('patient_id')->nullable()->after('anchored_to');

            $table->foreign('patient_id')->references('id')->on('clinical.patients');
            $table->index('patient_id');
        });

        // Backfill: case_annotations.patient_id from case_id → cases.patient_id
        DB::statement('
            UPDATE app.case_annotations a
            SET patient_id = c.patient_id
            FROM app.cases c
            WHERE a.case_id = c.id AND c.patient_id IS NOT NULL
        ');

        // 4. Follow-ups: add patient_id
        Schema::table('app.follow_ups', function (Blueprint $table) {
            $table->unsignedBigInteger('patient_id')->nullable()->after('decision_id');

            $table->foreign('patient_id')->references('id')->on('clinical.patients');
        });

        DB::statement("CREATE INDEX idx_follow_ups_patient_pending ON app.follow_ups(patient_id) WHERE status IN ('pending', 'in_progress')");

        // Backfill: follow_ups.patient_id from decision_id → decisions.case_id → cases.patient_id
        DB::statement('
            UPDATE app.follow_ups f
            SET patient_id = c.patient_id
            FROM app.decisions d
            JOIN app.cases c ON d.case_id = c.id
            WHERE f.decision_id = d.id AND c.patient_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::table('app.follow_ups', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropColumn('patient_id');
        });
        DB::statement('DROP INDEX IF EXISTS app.idx_follow_ups_patient_pending');

        Schema::table('app.case_annotations', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropIndex(['patient_id']);
            $table->dropColumn('patient_id');
        });

        Schema::table('app.case_discussions', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropIndex(['patient_id', 'domain']);
            $table->dropColumn(['domain', 'record_ref', 'patient_id']);
        });

        Schema::table('app.decisions', function (Blueprint $table) {
            $table->dropForeign(['patient_id']);
            $table->dropIndex(['patient_id']);
            $table->dropColumn(['patient_id', 'record_refs']);
        });
    }
};
```

- [ ] **Step 2: Update Decision model**

Add to `backend/app/Models/Decision.php` fillable array:
```php
// Add 'patient_id' and 'record_refs' to $fillable
```

Add to casts:
```php
'record_refs' => 'array',
```

Add relationship:
```php
public function patient(): BelongsTo
{
    return $this->belongsTo(\App\Models\Clinical\ClinicalPatient::class, 'patient_id');
}
```

- [ ] **Step 3: Update CaseDiscussion model**

Add `domain`, `record_ref`, `patient_id` to `$fillable`.

Add relationship:
```php
public function patient(): BelongsTo
{
    return $this->belongsTo(\App\Models\Clinical\ClinicalPatient::class, 'patient_id');
}
```

- [ ] **Step 4: Update CaseAnnotation model**

Add `patient_id` to `$fillable`.

Add relationship:
```php
public function patient(): BelongsTo
{
    return $this->belongsTo(\App\Models\Clinical\ClinicalPatient::class, 'patient_id');
}
```

- [ ] **Step 5: Update FollowUp model**

Add `patient_id` to `$fillable`.

Add relationship:
```php
public function patient(): BelongsTo
{
    return $this->belongsTo(\App\Models\Clinical\ClinicalPatient::class, 'patient_id');
}
```

- [ ] **Step 6: Update ClinicalPatient model with reverse relationships**

Add to `backend/app/Models/Clinical/ClinicalPatient.php`:
```php
public function flags(): HasMany
{
    return $this->hasMany(\App\Models\PatientFlag::class, 'patient_id');
}

public function tasks(): HasMany
{
    return $this->hasMany(\App\Models\PatientTask::class, 'patient_id');
}

public function decisions(): HasMany
{
    return $this->hasMany(\App\Models\Decision::class, 'patient_id');
}

public function followUps(): HasMany
{
    return $this->hasMany(\App\Models\FollowUp::class, 'patient_id');
}

public function discussions(): HasMany
{
    return $this->hasMany(\App\Models\CaseDiscussion::class, 'patient_id');
}
```

- [ ] **Step 7: Run migration**

Run: `cd /home/smudoshi/Github/Aurora/backend && php artisan migrate`
Expected: Columns added, backfill completes. Verify with: `php artisan tinker --execute="echo App\Models\Decision::whereNotNull('patient_id')->count();"`

- [ ] **Step 8: Commit**

```bash
git add backend/database/migrations/2026_03_22_200003_add_patient_anchoring_columns.php \
  backend/app/Models/Decision.php \
  backend/app/Models/CaseDiscussion.php \
  backend/app/Models/CaseAnnotation.php \
  backend/app/Models/FollowUp.php \
  backend/app/Models/Clinical/ClinicalPatient.php
git commit -m "feat: add patient anchoring columns to decisions, discussions, annotations, follow-ups"
```

---

### Task 4: RecordRef validation rule

**Files:**
- Create: `backend/app/Rules/ValidRecordRef.php`

- [ ] **Step 1: Create ValidRecordRef rule**

```php
<?php
// backend/app/Rules/ValidRecordRef.php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidRecordRef implements ValidationRule
{
    private const VALID_DOMAINS = [
        'condition', 'medication', 'procedure', 'measurement',
        'observation', 'genomic', 'imaging', 'general',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('The :attribute must be a string.');
            return;
        }

        if (!preg_match('/^[a-z]+:\d+$/', $value)) {
            $fail('The :attribute must be in the format "domain:id" (e.g., "genomic:42").');
            return;
        }

        $domain = explode(':', $value)[0];
        if (!in_array($domain, self::VALID_DOMAINS, true)) {
            $fail('The :attribute domain must be one of: ' . implode(', ', self::VALID_DOMAINS) . '.');
        }
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add backend/app/Rules/ValidRecordRef.php
git commit -m "feat: add ValidRecordRef validation rule"
```

---

### Task 5: PatientFlag controller and routes

**Files:**
- Create: `backend/app/Http/Controllers/PatientFlagController.php`
- Create: `backend/app/Http/Requests/StorePatientFlagRequest.php`
- Modify: `backend/routes/api.php`

- [ ] **Step 1: Create StorePatientFlagRequest**

```php
<?php
// backend/app/Http/Requests/StorePatientFlagRequest.php

namespace App\Http\Requests;

use App\Rules\ValidRecordRef;
use Illuminate\Foundation\Http\FormRequest;

class StorePatientFlagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled by middleware
    }

    public function rules(): array
    {
        return [
            'domain' => 'required|string|in:condition,medication,procedure,measurement,observation,genomic,imaging,general',
            'record_ref' => ['required', 'string', new ValidRecordRef()],
            'severity' => 'sometimes|string|in:critical,attention,informational',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
        ];
    }
}
```

- [ ] **Step 2: Create PatientFlagController**

```php
<?php
// backend/app/Http/Controllers/PatientFlagController.php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Http\Requests\StorePatientFlagRequest;
use App\Models\Clinical\ClinicalPatient;
use App\Models\PatientFlag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientFlagController extends Controller
{
    public function index(Request $request, int $patient): JsonResponse
    {
        $patientModel = ClinicalPatient::findOrFail($patient);
        $query = $patientModel->flags()->with(['flagger:id,name', 'resolver:id,name']);

        if ($request->has('domain')) {
            $query->forDomain($request->domain);
        }

        if ($request->has('resolved')) {
            if ($request->boolean('resolved')) {
                $query->whereNotNull('resolved_at');
            } else {
                $query->unresolved();
            }
        }

        $flags = $query->orderByDesc('created_at')->get();

        return ApiResponse::success($flags);
    }

    public function store(StorePatientFlagRequest $request, int $patient): JsonResponse
    {
        $patientModel = ClinicalPatient::findOrFail($patient);
        $flag = $patientModel->flags()->create([
            ...$request->validated(),
            'flagged_by' => $request->user()->id,
        ]);

        $flag->load('flagger:id,name');

        return ApiResponse::success($flag, 'Created', 201);
    }

    public function update(Request $request, int $flag): JsonResponse
    {
        $flag = PatientFlag::findOrFail($flag);
        $validated = $request->validate([
            'severity' => 'sometimes|string|in:critical,attention,informational',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
        ]);

        // Handle resolve action
        if ($request->boolean('resolve')) {
            $validated['resolved_at'] = now();
            $validated['resolved_by'] = $request->user()->id;
        }

        $flag->update($validated);
        $flag->load(['flagger:id,name', 'resolver:id,name']);

        return ApiResponse::success($flag);
    }

    public function destroy(Request $request, int $flag): JsonResponse
    {
        $flag = PatientFlag::findOrFail($flag);

        // Authorization: only creator or admin can delete
        if ($flag->flagged_by !== $request->user()->id && !$request->user()->hasRole('admin')) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $flag->delete();

        return ApiResponse::success(null, 200);
    }
}
```

- [ ] **Step 3: Add routes to api.php**

Add inside the `auth:sanctum` middleware group in `backend/routes/api.php`:

```php
// Patient Flags
Route::get('/patients/{patient}/flags', [PatientFlagController::class, 'index']);
Route::post('/patients/{patient}/flags', [PatientFlagController::class, 'store']);
Route::patch('/flags/{flag}', [PatientFlagController::class, 'update']);
Route::delete('/flags/{flag}', [PatientFlagController::class, 'destroy']);
```

Add the import at top:
```php
use App\Http\Controllers\PatientFlagController;
```

- [ ] **Step 4: Test manually**

Run: `cd /home/smudoshi/Github/Aurora/backend && php artisan route:list --path=flag`
Expected: 4 routes listed (GET, POST, PATCH, DELETE)

- [ ] **Step 5: Commit**

```bash
git add backend/app/Http/Controllers/PatientFlagController.php \
  backend/app/Http/Requests/StorePatientFlagRequest.php \
  backend/routes/api.php
git commit -m "feat: add PatientFlag API endpoints"
```

---

### Task 5: PatientTask controller and routes

**Files:**
- Create: `backend/app/Http/Controllers/PatientTaskController.php`
- Create: `backend/app/Http/Requests/StorePatientTaskRequest.php`
- Modify: `backend/routes/api.php`

- [ ] **Step 1: Create StorePatientTaskRequest**

```php
<?php
// backend/app/Http/Requests/StorePatientTaskRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePatientTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assigned_to' => 'nullable|integer|exists:app.users,id',
            'domain' => 'nullable|string|in:condition,medication,procedure,measurement,observation,genomic,imaging,general',
            'record_ref' => ['nullable', 'string', new \App\Rules\ValidRecordRef()],
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'due_date' => 'nullable|date|after_or_equal:today',
            'priority' => 'sometimes|string|in:low,normal,high,urgent',
        ];
    }
}
```

- [ ] **Step 2: Create PatientTaskController**

```php
<?php
// backend/app/Http/Controllers/PatientTaskController.php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Http\Requests\StorePatientTaskRequest;
use App\Models\Clinical\ClinicalPatient;
use App\Models\PatientTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientTaskController extends Controller
{
    public function index(Request $request, int $patient): JsonResponse
    {
        $patientModel = ClinicalPatient::findOrFail($patient);
        $query = $patientModel->tasks()->with(['creator:id,name', 'assignee:id,name']);

        if ($request->has('domain')) {
            $query->forDomain($request->domain);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        } else {
            $query->pending();
        }

        $tasks = $query->orderByDesc('created_at')->get();

        return ApiResponse::success($tasks);
    }

    public function store(StorePatientTaskRequest $request, int $patient): JsonResponse
    {
        $patientModel = ClinicalPatient::findOrFail($patient);
        $task = $patientModel->tasks()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        $task->load(['creator:id,name', 'assignee:id,name']);

        return ApiResponse::success($task, 'Created', 201);
    }

    public function update(Request $request, int $task): JsonResponse
    {
        $task = PatientTask::findOrFail($task);
        $validated = $request->validate([
            'assigned_to' => 'nullable|integer|exists:app.users,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:2000',
            'due_date' => 'nullable|date',
            'priority' => 'sometimes|string|in:low,normal,high,urgent',
            'status' => 'sometimes|string|in:pending,in_progress,completed,cancelled',
        ]);

        // Auto-set completed fields
        if (($validated['status'] ?? null) === 'completed') {
            $validated['completed_at'] = now();
            $validated['completed_by'] = $request->user()->id;
        }

        $task->update($validated);
        $task->load(['creator:id,name', 'assignee:id,name']);

        return ApiResponse::success($task);
    }

    public function destroy(Request $request, int $task): JsonResponse
    {
        $task = PatientTask::findOrFail($task);

        // Authorization: only creator or admin can delete
        if ($task->created_by !== $request->user()->id && !$request->user()->hasRole('admin')) {
            return ApiResponse::error('Unauthorized', 403);
        }

        $task->delete();

        return ApiResponse::success(null, 200);
    }
}
```

- [ ] **Step 3: Add routes to api.php**

```php
// Patient Tasks
Route::get('/patients/{patient}/tasks', [PatientTaskController::class, 'index']);
Route::post('/patients/{patient}/tasks', [PatientTaskController::class, 'store']);
Route::patch('/tasks/{task}', [PatientTaskController::class, 'update']);
Route::delete('/tasks/{task}', [PatientTaskController::class, 'destroy']);
```

Add import: `use App\Http\Controllers\PatientTaskController;`

- [ ] **Step 4: Verify routes**

Run: `cd /home/smudoshi/Github/Aurora/backend && php artisan route:list --path=task`
Expected: 4 routes listed.

- [ ] **Step 5: Commit**

```bash
git add backend/app/Http/Controllers/PatientTaskController.php \
  backend/app/Http/Requests/StorePatientTaskRequest.php \
  backend/routes/api.php
git commit -m "feat: add PatientTask API endpoints"
```

---

### Task 6: Patient collaboration aggregate endpoint

**Files:**
- Create: `backend/app/Http/Controllers/PatientCollaborationController.php`
- Modify: `backend/routes/api.php`

- [ ] **Step 1: Create PatientCollaborationController**

```php
<?php
// backend/app/Http/Controllers/PatientCollaborationController.php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\Clinical\ClinicalPatient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientCollaborationController extends Controller
{
    public function index(Request $request, int $patient): JsonResponse
    {
        $patientModel = ClinicalPatient::findOrFail($patient);
        $domain = $request->get('domain');

        // Discussions for this patient
        $discussionsQuery = $patientModel->discussions()
            ->with(['user:id,name,avatar'])
            ->orderByDesc('created_at')
            ->limit(10);
        if ($domain) {
            $discussionsQuery->where('domain', $domain);
        }

        // Standalone tasks
        $tasksQuery = $patientModel->tasks()
            ->with(['assignee:id,name', 'creator:id,name'])
            ->pending()
            ->orderByDesc('created_at')
            ->limit(10);
        if ($domain) {
            $tasksQuery->forDomain($domain);
        }

        // Follow-ups from decisions
        $followUpsQuery = $patientModel->followUps()
            ->with(['assignee:id,name', 'decision:id,recommendation'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderByDesc('created_at')
            ->limit(10);

        // Flags
        $flagsQuery = $patientModel->flags()
            ->with(['flagger:id,name'])
            ->unresolved()
            ->orderByDesc('created_at')
            ->limit(10);
        if ($domain) {
            $flagsQuery->forDomain($domain);
        }

        // Decisions
        $decisionsQuery = $patientModel->decisions()
            ->with(['proposer:id,name', 'votes:id,decision_id,user_id,vote', 'clinicalCase:id,title'])
            ->orderByDesc('created_at')
            ->limit(10);

        return ApiResponse::success([
            'discussions' => $discussionsQuery->get(),
            'tasks' => $tasksQuery->get(),
            'follow_ups' => $followUpsQuery->get(),
            'flags' => $flagsQuery->get(),
            'decisions' => $decisionsQuery->get(),
        ]);
    }
}
```

- [ ] **Step 2: Add route**

```php
// Patient Collaboration (aggregate)
Route::get('/patients/{patient}/collaboration', [PatientCollaborationController::class, 'index']);
```

Add import: `use App\Http\Controllers\PatientCollaborationController;`

- [ ] **Step 3: Add patient decisions convenience route**

```php
// Patient Decisions (read-only convenience)
Route::get('/patients/{patient}/decisions', function (ClinicalPatient $patient) {
    $decisions = $patient->decisions()
        ->with(['proposer:id,name', 'votes', 'followUps', 'clinicalCase:id,title', 'session:id,title'])
        ->orderByDesc('created_at')
        ->get();
    return ApiResponse::success($decisions);
});
```

Add import at top: `use App\Models\Clinical\ClinicalPatient;`

- [ ] **Step 4: Verify routes**

Run: `cd /home/smudoshi/Github/Aurora/backend && php artisan route:list --path=patients`
Expected: collaboration + decisions routes appear.

- [ ] **Step 5: Commit**

```bash
git add backend/app/Http/Controllers/PatientCollaborationController.php backend/routes/api.php
git commit -m "feat: add patient collaboration aggregate and decisions endpoints"
```

---

### Task 7: Frontend collaboration types

**Files:**
- Create: `frontend/src/features/patient-profile/types/collaboration.ts`

- [ ] **Step 1: Create collaboration types**

```typescript
// frontend/src/features/patient-profile/types/collaboration.ts

export type ClinicalDomain =
  | 'condition'
  | 'medication'
  | 'procedure'
  | 'measurement'
  | 'observation'
  | 'genomic'
  | 'imaging'
  | 'general';

export type FlagSeverity = 'critical' | 'attention' | 'informational';
export type TaskPriority = 'low' | 'normal' | 'high' | 'urgent';
export type TaskStatus = 'pending' | 'in_progress' | 'completed' | 'cancelled';

export interface UserRef {
  id: number;
  name: string;
  avatar?: string;
}

export interface PatientFlag {
  id: number;
  patient_id: number;
  flagged_by: number;
  flagger?: UserRef;
  domain: ClinicalDomain;
  record_ref: string;
  severity: FlagSeverity;
  title: string;
  description: string | null;
  resolved_at: string | null;
  resolved_by: number | null;
  resolver?: UserRef | null;
  created_at: string;
  updated_at: string;
}

export interface PatientTask {
  id: number;
  patient_id: number;
  created_by: number;
  creator?: UserRef;
  assigned_to: number | null;
  assignee?: UserRef | null;
  domain: ClinicalDomain | null;
  record_ref: string | null;
  title: string;
  description: string | null;
  due_date: string | null;
  priority: TaskPriority;
  status: TaskStatus;
  completed_at: string | null;
  completed_by: number | null;
  created_at: string;
  updated_at: string;
}

export interface FollowUp {
  id: number;
  decision_id: number;
  decision?: { id: number; recommendation: string };
  assigned_to: number | null;
  assignee?: UserRef | null;
  title: string;
  description: string | null;
  due_date: string | null;
  status: TaskStatus;
  completed_at: string | null;
  patient_id: number | null;
  created_at: string;
  updated_at: string;
}

export interface DecisionVote {
  id: number;
  decision_id: number;
  user_id: number;
  vote: 'agree' | 'disagree' | 'abstain';
}

export interface PatientDecision {
  id: number;
  case_id: number;
  clinical_case?: { id: number; title: string };
  session_id: number | null;
  session?: { id: number; title: string } | null;
  proposed_by: number;
  proposer?: UserRef;
  patient_id: number | null;
  decision_type: string;
  recommendation: string;
  rationale: string | null;
  status: string;
  urgency: string;
  votes?: DecisionVote[];
  follow_ups?: FollowUp[];
  record_refs: string[] | null;
  finalized_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface AnchoredDiscussion {
  id: number;
  case_id: number;
  user_id: number;
  user?: UserRef;
  parent_id: number | null;
  content: string;
  domain: ClinicalDomain | null;
  record_ref: string | null;
  patient_id: number | null;
  created_at: string;
  replies?: AnchoredDiscussion[];
}

export interface CollaborationData {
  discussions: AnchoredDiscussion[];
  tasks: PatientTask[];
  follow_ups: FollowUp[];
  flags: PatientFlag[];
  decisions: PatientDecision[];
}

// Helper to map view tab name to domain filter
export const VIEW_TAB_TO_DOMAIN: Record<string, ClinicalDomain | undefined> = {
  briefing: undefined, // show all
  timeline: undefined,
  labs: 'measurement',
  imaging: 'imaging',
  genomics: 'genomic',
  notes: undefined, // notes span domains
  visits: undefined,
  similar: undefined,
};
```

- [ ] **Step 2: Commit**

```bash
git add frontend/src/features/patient-profile/types/collaboration.ts
git commit -m "feat: add frontend collaboration types for flags, tasks, decisions"
```

---

### Task 8: Frontend collaboration API and hooks

**Files:**
- Create: `frontend/src/features/patient-profile/api/collaborationApi.ts`
- Create: `frontend/src/features/patient-profile/hooks/useCollaboration.ts`

- [ ] **Step 1: Create collaboration API layer**

```typescript
// frontend/src/features/patient-profile/api/collaborationApi.ts

import api from '@/lib/api-client';
import type {
  CollaborationData,
  PatientFlag,
  PatientTask,
  PatientDecision,
  ClinicalDomain,
} from '../types/collaboration';

interface ApiResponse<T> {
  success: boolean;
  data: T;
}

function unwrap<T>(response: { data: ApiResponse<T> }): T {
  return response.data.data;
}

// ── Flags ────────────────────────────────────────────────────────────

export async function fetchPatientFlags(
  patientId: number,
  domain?: ClinicalDomain,
  resolved?: boolean,
): Promise<PatientFlag[]> {
  const params: Record<string, string> = {};
  if (domain) params.domain = domain;
  if (resolved !== undefined) params.resolved = String(resolved);
  return unwrap(await api.get(`/patients/${patientId}/flags`, { params }));
}

export async function createPatientFlag(
  patientId: number,
  data: { domain: ClinicalDomain; record_ref: string; severity?: string; title: string; description?: string },
): Promise<PatientFlag> {
  return unwrap(await api.post(`/patients/${patientId}/flags`, data));
}

export async function updatePatientFlag(
  flagId: number,
  data: { severity?: string; title?: string; description?: string; resolve?: boolean },
): Promise<PatientFlag> {
  return unwrap(await api.patch(`/flags/${flagId}`, data));
}

export async function deletePatientFlag(flagId: number): Promise<void> {
  await api.delete(`/flags/${flagId}`);
}

// ── Tasks ────────────────────────────────────────────────────────────

export async function fetchPatientTasks(
  patientId: number,
  domain?: ClinicalDomain,
  status?: string,
): Promise<PatientTask[]> {
  const params: Record<string, string> = {};
  if (domain) params.domain = domain;
  if (status) params.status = status;
  return unwrap(await api.get(`/patients/${patientId}/tasks`, { params }));
}

export async function createPatientTask(
  patientId: number,
  data: {
    title: string;
    description?: string;
    assigned_to?: number;
    domain?: ClinicalDomain;
    record_ref?: string;
    due_date?: string;
    priority?: string;
  },
): Promise<PatientTask> {
  return unwrap(await api.post(`/patients/${patientId}/tasks`, data));
}

export async function updatePatientTask(
  taskId: number,
  data: { status?: string; assigned_to?: number; title?: string; description?: string; due_date?: string; priority?: string },
): Promise<PatientTask> {
  return unwrap(await api.patch(`/tasks/${taskId}`, data));
}

export async function deletePatientTask(taskId: number): Promise<void> {
  await api.delete(`/tasks/${taskId}`);
}

// ── Collaboration Aggregate ──────────────────────────────────────────

export async function fetchPatientCollaboration(
  patientId: number,
  domain?: ClinicalDomain,
): Promise<CollaborationData> {
  const params: Record<string, string> = {};
  if (domain) params.domain = domain;
  return unwrap(await api.get(`/patients/${patientId}/collaboration`, { params }));
}

// ── Decisions (read-only convenience) ────────────────────────────────

export async function fetchPatientDecisions(patientId: number): Promise<PatientDecision[]> {
  return unwrap(await api.get(`/patients/${patientId}/decisions`));
}
```

- [ ] **Step 2: Create collaboration hooks**

```typescript
// frontend/src/features/patient-profile/hooks/useCollaboration.ts

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import {
  fetchPatientFlags,
  createPatientFlag,
  updatePatientFlag,
  deletePatientFlag,
  fetchPatientTasks,
  createPatientTask,
  updatePatientTask,
  deletePatientTask,
  fetchPatientCollaboration,
  fetchPatientDecisions,
} from '../api/collaborationApi';
import type { ClinicalDomain } from '../types/collaboration';

// ── Flags ────────────────────────────────────────────────────────────

export function usePatientFlags(patientId: number | undefined, domain?: ClinicalDomain) {
  return useQuery({
    queryKey: ['patient-flags', patientId, domain],
    queryFn: () => fetchPatientFlags(patientId!, domain, false),
    enabled: !!patientId,
    staleTime: 30_000,
  });
}

export function useCreateFlag(patientId: number | undefined) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: Parameters<typeof createPatientFlag>[1]) =>
      createPatientFlag(patientId!, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['patient-flags', patientId] });
      qc.invalidateQueries({ queryKey: ['patient-collaboration', patientId] });
    },
  });
}

export function useUpdateFlag(patientId: number | undefined) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ flagId, data }: { flagId: number; data: Parameters<typeof updatePatientFlag>[1] }) =>
      updatePatientFlag(flagId, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['patient-flags', patientId] });
      qc.invalidateQueries({ queryKey: ['patient-collaboration', patientId] });
    },
  });
}

export function useDeleteFlag(patientId: number | undefined) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (flagId: number) => deletePatientFlag(flagId),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['patient-flags', patientId] });
      qc.invalidateQueries({ queryKey: ['patient-collaboration', patientId] });
    },
  });
}

// ── Tasks ────────────────────────────────────────────────────────────

export function usePatientTasks(patientId: number | undefined, domain?: ClinicalDomain) {
  return useQuery({
    queryKey: ['patient-tasks', patientId, domain],
    queryFn: () => fetchPatientTasks(patientId!, domain),
    enabled: !!patientId,
    staleTime: 30_000,
  });
}

export function useCreateTask(patientId: number | undefined) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (data: Parameters<typeof createPatientTask>[1]) =>
      createPatientTask(patientId!, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['patient-tasks', patientId] });
      qc.invalidateQueries({ queryKey: ['patient-collaboration', patientId] });
    },
  });
}

export function useUpdateTask(patientId: number | undefined) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: ({ taskId, data }: { taskId: number; data: Parameters<typeof updatePatientTask>[1] }) =>
      updatePatientTask(taskId, data),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['patient-tasks', patientId] });
      qc.invalidateQueries({ queryKey: ['patient-collaboration', patientId] });
    },
  });
}

export function useDeleteTask(patientId: number | undefined) {
  const qc = useQueryClient();
  return useMutation({
    mutationFn: (taskId: number) => deletePatientTask(taskId),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['patient-tasks', patientId] });
      qc.invalidateQueries({ queryKey: ['patient-collaboration', patientId] });
    },
  });
}

// ── Follow-ups (standalone query for Briefing) ──────────────────────

export function usePatientFollowUps(patientId: number | undefined) {
  // Follow-ups are included in the collaboration aggregate,
  // but this dedicated hook is for the Briefing's PendingActions quadrant
  // which needs follow-ups without loading the full aggregate.
  return useQuery({
    queryKey: ['patient-follow-ups', patientId],
    queryFn: async () => {
      const collab = await fetchPatientCollaboration(patientId!);
      return collab.follow_ups;
    },
    enabled: !!patientId,
    staleTime: 30_000,
  });
}

// ── Collaboration Aggregate ──────────────────────────────────────────

export function usePatientCollaboration(patientId: number | undefined, domain?: ClinicalDomain) {
  return useQuery({
    queryKey: ['patient-collaboration', patientId, domain],
    queryFn: () => fetchPatientCollaboration(patientId!, domain),
    enabled: !!patientId,
    staleTime: 15_000,
  });
}

// ── Decisions ────────────────────────────────────────────────────────

export function usePatientDecisions(patientId: number | undefined) {
  return useQuery({
    queryKey: ['patient-decisions', patientId],
    queryFn: () => fetchPatientDecisions(patientId!),
    enabled: !!patientId,
    staleTime: 30_000,
  });
}
```

- [ ] **Step 3: Commit**

```bash
git add frontend/src/features/patient-profile/api/collaborationApi.ts \
  frontend/src/features/patient-profile/hooks/useCollaboration.ts
git commit -m "feat: add collaboration API and TanStack Query hooks"
```

---

### Task 9: PatientBriefing component

**Files:**
- Create: `frontend/src/features/patient-profile/components/PatientBriefing.tsx`
- Create: `frontend/src/features/patient-profile/components/ActiveProblemsList.tsx`
- Create: `frontend/src/features/patient-profile/components/FlaggedFindings.tsx`
- Create: `frontend/src/features/patient-profile/components/PendingActions.tsx`
- Create: `frontend/src/features/patient-profile/components/RecentDecisions.tsx`

This is the largest task. Build the four-quadrant briefing dashboard as described in the spec. Each quadrant is a separate component composed into PatientBriefing.

- [ ] **Step 1: Create ActiveProblemsList**

Renders active conditions (no end_date) and active medications from the PatientProfile data. New conditions (added in last 14 days) get a "NEW" badge. Each item is clickable to navigate to the relevant data view.

Props: `{ conditions: ClinicalEvent[], medications: ClinicalEvent[], onNavigate: (tab: string, filter?: string) => void }`

Filter logic:
- Active conditions: `conditions.filter(c => !c.end_date)`
- Active medications: `medications.filter(m => !m.end_date)`
- New badge: `new Date(item.start_date) > Date.now() - 14 * 86400000`

Display: List with name, date, and optional "NEW" badge. Uses the same color scheme as existing domain badges (condition: green, medication: blue).

- [ ] **Step 2: Create FlaggedFindings**

Renders unresolved PatientFlags sorted by severity (critical first, then attention, then informational).

Props: `{ flags: PatientFlag[], onResolve: (flagId: number) => void, onNavigate: (recordRef: string) => void }`

Display: Severity dot (red/amber/blue) + title. Clickable to navigate to the source data point.

- [ ] **Step 3: Create PendingActions**

Combines two data sources: PatientTask[] (standalone) and FollowUp[] (from decisions). Shows unified task list with checkbox to mark complete.

Props: `{ tasks: PatientTask[], followUps: FollowUp[], onCompleteTask: (taskId: number) => void, onCompleteFollowUp: (followUpId: number) => void }`

Display: Checkbox + title + assignee name + due date (overdue highlighted in red).

- [ ] **Step 4: Create RecentDecisions**

Renders recent Decision objects with vote summary and status badge.

Props: `{ decisions: PatientDecision[] }`

Display: Recommendation text, decision_type badge, status badge (proposed/approved/etc), vote tally (N agree, N disagree), source case title, date.

- [ ] **Step 5: Create PatientBriefing**

Composes the four quadrants into a 2x2 grid. Fetches collaboration data via `usePatientCollaboration(patientId)` and profile data from parent.

Props: `{ patientId: number, profile: PatientProfile, onNavigate: (tab: string) => void }`

Layout: CSS Grid `grid-template-columns: 1fr 1fr` with consistent section headers (uppercase, colored labels matching the mockup). Handles loading state with skeleton placeholders. Shows empty states per spec.

- [ ] **Step 6: Commit**

```bash
git add frontend/src/features/patient-profile/components/ActiveProblemsList.tsx \
  frontend/src/features/patient-profile/components/FlaggedFindings.tsx \
  frontend/src/features/patient-profile/components/PendingActions.tsx \
  frontend/src/features/patient-profile/components/RecentDecisions.tsx \
  frontend/src/features/patient-profile/components/PatientBriefing.tsx
git commit -m "feat: add PatientBriefing component with four quadrants"
```

---

### Task 10: Integrate Briefing into PatientProfilePage

**Files:**
- Modify: `frontend/src/features/patient-profile/pages/PatientProfilePage.tsx`
- Modify: `frontend/src/features/patient-profile/components/PatientDemographicsCard.tsx`

- [ ] **Step 1: Update ViewMode type**

In `PatientProfilePage.tsx`, change the ViewMode type:

```typescript
// Before
type ViewMode = 'timeline' | 'list' | 'labs' | 'visits' | 'notes' | 'eras' | 'imaging' | 'genomics' | 'similar';

// After
type ViewMode = 'briefing' | 'timeline' | 'list' | 'labs' | 'visits' | 'notes' | 'imaging' | 'genomics' | 'similar';
```

- [ ] **Step 2: Change default view mode**

```typescript
// Before
const [viewMode, setViewMode] = useState<ViewMode>('timeline');

// After
const [viewMode, setViewMode] = useState<ViewMode>('briefing');
```

- [ ] **Step 3: Add Briefing tab button**

In the tab bar, add "Briefing" as the first button and remove "Eras". The Briefing tab should be visually distinct (e.g., slightly different accent color or bold).

- [ ] **Step 4: Add Briefing case to render switch**

```typescript
{viewMode === 'briefing' && profile && (
  <PatientBriefing
    patientId={Number(personId)}
    profile={profile}
    onNavigate={(tab) => setViewMode(tab as ViewMode)}
  />
)}
```

- [ ] **Step 5: Remove Eras tab rendering**

Remove the `viewMode === 'eras'` case and the EraTimeline import.

- [ ] **Step 6: Compact PatientDemographicsCard**

In `PatientDemographicsCard.tsx`, remove the mini-stats bar (event counts) since this information now lives in the Briefing. Keep: avatar, name, MRN, age, sex, race, ethnicity, deceased badge. Add: primary diagnosis tag and upcoming session tag if applicable.

- [ ] **Step 7: Verify in browser**

Open http://localhost:5177/profiles/{patientId} — Briefing should be the default view showing the four quadrants. Other tabs should still work.

- [ ] **Step 8: Commit**

```bash
git add frontend/src/features/patient-profile/pages/PatientProfilePage.tsx \
  frontend/src/features/patient-profile/components/PatientDemographicsCard.tsx
git commit -m "feat: integrate Briefing as default patient view, remove Eras tab"
```

---

## Phase 2: Inline Actions

### Task 11: InlineActionMenu component

**Files:**
- Create: `frontend/src/features/patient-profile/components/InlineActionMenu.tsx`

- [ ] **Step 1: Build InlineActionMenu**

A context menu component triggered by a three-dot button (primary) or right-click (secondary).

Props:
```typescript
interface InlineActionMenuProps {
  recordRef: string;         // e.g., "genomic:42"
  domain: ClinicalDomain;
  patientId: number;
  onFlag?: () => void;       // callback after flag created
  onTask?: () => void;       // callback after task created
  onDiscuss?: () => void;    // callback to open panel
}
```

Features:
- Three-dot button (Lucide `MoreVertical` icon) shown on hover of parent row
- Click opens a dropdown with 4 actions: Flag for review, Add to discussion, Create task, Annotate
- "Flag for review" opens an inline form (title + severity dropdown) below the menu
- "Create task" opens an inline form (title + assign to + due date)
- "Add to discussion" triggers `onDiscuss` to open the collaboration panel
- Right-click handler: attaches to parent element, calls `preventDefault()` only if target matches `[data-action-row]`
- Menu positioned with `position: absolute` relative to trigger, with viewport boundary detection
- Uses existing design tokens: `var(--surface-elevated)`, `var(--border-subtle)`, `var(--text-primary)`

- [ ] **Step 2: Commit**

```bash
git add frontend/src/features/patient-profile/components/InlineActionMenu.tsx
git commit -m "feat: add InlineActionMenu component with flag and task creation"
```

---

### Task 12: SelectActToolbar component

**Files:**
- Create: `frontend/src/features/patient-profile/components/SelectActToolbar.tsx`

- [ ] **Step 1: Build SelectActToolbar**

A floating toolbar that appears when data rows are selected via checkboxes.

Props:
```typescript
interface SelectActToolbarProps {
  selectedCount: number;
  selectedRefs: string[];    // array of record_refs
  domain: ClinicalDomain;
  patientId: number;
  onClear: () => void;
  onDiscuss: () => void;     // open panel with selected refs
  onFlag: () => void;        // batch flag
  onExport: () => void;      // CSV export of selected
}
```

Features:
- Fixed position at bottom of viewport (above any existing footer), centered
- Shows: "{N} selected:" + action buttons (Discuss, Flag, Export)
- Smooth slide-up animation on appear (framer-motion)
- Discuss opens collaboration panel, Flag creates flags for all selected items
- Export generates CSV for selected items

- [ ] **Step 2: Commit**

```bash
git add frontend/src/features/patient-profile/components/SelectActToolbar.tsx
git commit -m "feat: add SelectActToolbar for batch actions on data rows"
```

---

### Task 13: Add inline actions to GenomicsTab and LabPanel

**Files:**
- Modify: `frontend/src/features/patient-profile/components/PatientGenomicsTab.tsx`
- Modify: `frontend/src/features/patient-profile/components/PatientLabPanel.tsx`

- [ ] **Step 1: Add selection state and inline menu to GenomicsTab**

In `PatientGenomicsTab.tsx`:
- Add `useState<Set<number>>` for selected variant IDs
- Add checkbox column to variant table rows
- Add `data-action-row` attribute to each row
- Add `InlineActionMenu` trigger (three-dot button) to each row
- Add `SelectActToolbar` when selection is non-empty
- Add annotation indicator badge showing thread/flag count per variant (from collaboration data)

Props addition: `patientId: number` (needed for action menus)

- [ ] **Step 2: Add selection state and inline menu to LabPanel**

In `PatientLabPanel.tsx`:
- Add checkbox to each lab measurement card header
- Add `InlineActionMenu` trigger to each measurement card
- Add `SelectActToolbar` when selection is non-empty
- Record refs for labs use format `measurement:{id}`

Props addition: `patientId: number`

- [ ] **Step 3: Verify in browser**

Open Genomics and Labs tabs. Verify three-dot menu appears on hover, checkbox selection works, and toolbar appears at bottom.

- [ ] **Step 4: Commit**

```bash
git add frontend/src/features/patient-profile/components/PatientGenomicsTab.tsx \
  frontend/src/features/patient-profile/components/PatientLabPanel.tsx
git commit -m "feat: add inline actions and selection to Genomics and Labs views"
```

---

### Task 14: Add inline actions to remaining data views

**Files:**
- Modify: `frontend/src/features/patient-profile/components/PatientNotesTab.tsx`
- Modify: `frontend/src/features/patient-profile/components/PatientVisitView.tsx`
- Modify: `frontend/src/features/patient-profile/components/PatientImagingTab.tsx`

- [ ] **Step 1: Add InlineActionMenu to NotesTab**

Add three-dot menu to each note card. Domain: determined by note_type or 'general'. No checkbox selection for notes (notes are typically referenced individually).

- [ ] **Step 2: Add InlineActionMenu to VisitView**

Add three-dot menu to each visit card and to individual event rows within expanded visits. Domain: matches the event's domain.

- [ ] **Step 3: Add InlineActionMenu to ImagingTab**

Add three-dot menu to each imaging study card. Domain: 'imaging'. Record ref: `imaging:{study_id}`.

- [ ] **Step 4: Commit**

```bash
git add frontend/src/features/patient-profile/components/PatientNotesTab.tsx \
  frontend/src/features/patient-profile/components/PatientVisitView.tsx \
  frontend/src/features/patient-profile/components/PatientImagingTab.tsx
git commit -m "feat: add inline actions to Notes, Visits, and Imaging views"
```

---

## Phase 3: Collaboration Panel

### Task 15: CollaborationPanel shell

**Files:**
- Create: `frontend/src/features/patient-profile/components/CollaborationPanel.tsx`

- [ ] **Step 1: Build panel shell**

A slide-out panel from the right side, 320px wide.

Props:
```typescript
interface CollaborationPanelProps {
  patientId: number;
  domain?: ClinicalDomain;    // from VIEW_TAB_TO_DOMAIN[activeTab]
  isOpen: boolean;
  onClose: () => void;
  initialTab?: 'discuss' | 'tasks' | 'flags' | 'decisions';
  initialRecordRef?: string;  // when opened from inline action
}
```

Features:
- Slides in from right with framer-motion `animate={{ x: 0 }}` / `exit={{ x: 320 }}`
- Header shows domain-specific title (e.g., "Genomics Context") with close button
- Four tabs: Discuss, Tasks, Flags, Decisions
- Fetches data via `usePatientCollaboration(patientId, domain)`
- When domain changes (tab switch in main view), refetches with new domain

- [ ] **Step 2: Commit**

```bash
git add frontend/src/features/patient-profile/components/CollaborationPanel.tsx
git commit -m "feat: add CollaborationPanel shell with slide-out animation"
```

---

### Task 16: Panel tab components

**Files:**
- Create: `frontend/src/features/patient-profile/components/PanelDiscussionTab.tsx`
- Create: `frontend/src/features/patient-profile/components/PanelTasksTab.tsx`
- Create: `frontend/src/features/patient-profile/components/PanelFlagsTab.tsx`
- Create: `frontend/src/features/patient-profile/components/PanelDecisionsTab.tsx`

- [ ] **Step 1: Build PanelDiscussionTab**

Shows filtered discussions with quick-compose form at bottom.

Props: `{ discussions: AnchoredDiscussion[], patientId: number, domain?: ClinicalDomain }`

Features:
- Thread cards: author avatar/initials, name, timestamp, content preview, reply count
- Quick-compose: text input + Post button at bottom
- Creates discussions via existing `casesApi.createDiscussion()` with added domain + record_ref fields

- [ ] **Step 2: Build PanelTasksTab**

Shows combined tasks (PatientTask) and follow-ups (FollowUp).

Props: `{ tasks: PatientTask[], followUps: FollowUp[], patientId: number, onComplete: (type: 'task'|'followup', id: number) => void }`

Features:
- Unified list sorted by due_date (overdue first)
- Checkbox to mark complete
- "New Task" form: title + assign to + due date
- Visual distinction between standalone tasks and decision follow-ups (follow-ups show linked decision)

- [ ] **Step 3: Build PanelFlagsTab**

Shows unresolved flags with resolve action.

Props: `{ flags: PatientFlag[], onResolve: (flagId: number) => void }`

Features:
- Severity dot + title + description
- "Resolve" button on each flag
- Link to source data point (parse record_ref to navigate)

- [ ] **Step 4: Build PanelDecisionsTab**

Shows recent decisions for this patient.

Props: `{ decisions: PatientDecision[] }`

Features:
- Recommendation text, status badge, vote tally
- Linked follow-ups shown nested
- Source case title as link

- [ ] **Step 5: Commit**

```bash
git add frontend/src/features/patient-profile/components/PanelDiscussionTab.tsx \
  frontend/src/features/patient-profile/components/PanelTasksTab.tsx \
  frontend/src/features/patient-profile/components/PanelFlagsTab.tsx \
  frontend/src/features/patient-profile/components/PanelDecisionsTab.tsx
git commit -m "feat: add collaboration panel tab components"
```

---

### Task 17: Integrate CollaborationPanel into PatientProfilePage

**Files:**
- Modify: `frontend/src/features/patient-profile/pages/PatientProfilePage.tsx`

- [ ] **Step 1: Add panel state**

```typescript
const [panelOpen, setPanelOpen] = useState(false);
const [panelTab, setPanelTab] = useState<'discuss' | 'tasks' | 'flags' | 'decisions'>('discuss');
const [panelRecordRef, setPanelRecordRef] = useState<string | undefined>();
```

- [ ] **Step 2: Add "Collaborate" button to tab bar**

Add at the right end of the tab bar:
```tsx
<button
  onClick={() => setPanelOpen(prev => !prev)}
  className="ml-auto px-3 py-1.5 rounded text-xs font-semibold bg-[var(--accent)]/15 text-[var(--accent)]"
>
  Collaborate &raquo;
</button>
```

- [ ] **Step 3: Render CollaborationPanel**

Add at the end of the page layout, alongside the main content area:
```tsx
<CollaborationPanel
  patientId={Number(personId)}
  domain={VIEW_TAB_TO_DOMAIN[viewMode]}
  isOpen={panelOpen}
  onClose={() => setPanelOpen(false)}
  initialTab={panelTab}
  initialRecordRef={panelRecordRef}
/>
```

- [ ] **Step 4: Wire inline actions to open panel**

Pass callbacks through to InlineActionMenu in each data view:
- `onDiscuss` → `setPanelOpen(true); setPanelTab('discuss'); setPanelRecordRef(ref);`

- [ ] **Step 5: Add keyboard shortcut (Cmd/Ctrl + Shift + C)**

Add a `useEffect` in PatientProfilePage for the keyboard shortcut:
```typescript
useEffect(() => {
  const handler = (e: KeyboardEvent) => {
    if ((e.metaKey || e.ctrlKey) && e.shiftKey && e.key === 'c') {
      e.preventDefault();
      setPanelOpen(prev => !prev);
    }
  };
  window.addEventListener('keydown', handler);
  return () => window.removeEventListener('keydown', handler);
}, []);
```

- [ ] **Step 6: Adjust main content width when panel is open**

When `panelOpen`, the main content area should shrink by 320px:
```tsx
<div className={`flex-1 transition-all ${panelOpen ? 'mr-[320px]' : ''}`}>
```

- [ ] **Step 6: Verify in browser**

Open any patient profile. Click "Collaborate" — panel should slide in. Switch tabs — panel content should re-filter. Click three-dot menu → "Add to discussion" on a variant — panel should open to Discuss tab.

- [ ] **Step 7: Commit**

```bash
git add frontend/src/features/patient-profile/pages/PatientProfilePage.tsx
git commit -m "feat: integrate CollaborationPanel into patient profile page"
```

---

## Phase 4: Session Agenda Enhancement

### Task 18: SessionAgenda component

**Files:**
- Create: `frontend/src/features/collaboration/components/SessionAgenda.tsx`

- [ ] **Step 1: Build SessionAgenda**

An ordered list of cases for a session, showing patient info and flag counts.

Props:
```typescript
interface SessionAgendaProps {
  sessionId: number;
  sessionCases: SessionCaseWithPatient[];
  onReorder: (caseId: number, newOrder: number) => void;
  onRemove: (caseId: number) => void;
}
```

Features:
- Numbered list of cases with patient name, MRN, one-line summary
- Flag count badge per patient (fetched via `usePatientFlags`)
- Presenter name and time allotment
- Status indicator per case (pending/presenting/discussed/skipped)
- "Open Patient" link to `/profiles/{patientId}`
- Drag-to-reorder using native HTML drag-and-drop (or simple up/down arrows for initial implementation)
- "Add Case" button at bottom

- [ ] **Step 2: Commit**

```bash
git add frontend/src/features/collaboration/components/SessionAgenda.tsx
git commit -m "feat: add SessionAgenda component for multi-case meeting view"
```

---

### Task 19: SessionDecisionLog component

**Files:**
- Create: `frontend/src/features/collaboration/components/SessionDecisionLog.tsx`

- [ ] **Step 1: Build SessionDecisionLog**

Per-case decision capture with voting, grouped by patient.

Props:
```typescript
interface SessionDecisionLogProps {
  sessionId: number;
  decisions: PatientDecision[];
  sessionCases: SessionCaseWithPatient[];
}
```

Features:
- Decisions grouped by case/patient
- Each decision shows: recommendation, type badge, status, vote tally
- "Propose Decision" form: case selector, recommendation text, type dropdown, urgency
- Vote buttons (agree/disagree/abstain) on each decision
- Follow-up creation from decision

- [ ] **Step 2: Commit**

```bash
git add frontend/src/features/collaboration/components/SessionDecisionLog.tsx
git commit -m "feat: add SessionDecisionLog for per-case decision capture"
```

---

### Task 20: Simplify CaseDetailPage

**Files:**
- Modify: `frontend/src/features/cases/pages/CaseDetailPage.tsx`

- [ ] **Step 1: Update tab structure**

Current tabs: Overview, Discussion, Annotations, Documents, Decisions, Team

New tabs: Overview, Documents, Team

- Move Discussion and Annotations content to the patient-level collaboration panel (these are now accessed from the patient page)
- Move Decisions to session-level (accessed from the session page)
- Keep: Overview (case metadata, clinical question), Documents (shared reference materials), Team (member management)

- [ ] **Step 2: Enhance Overview tab**

Add to the Overview tab:
- Link to patient profile page (prominent "Open Patient" button)
- Link to session(s) this case belongs to
- Summary of flag count and pending task count for this patient
- Most recent decision for this case

- [ ] **Step 3: Verify in browser**

Open a case detail page. Verify simplified tab structure. Verify links to patient and session work.

- [ ] **Step 4: Commit**

```bash
git add frontend/src/features/cases/pages/CaseDetailPage.tsx
git commit -m "refactor: simplify CaseDetailPage, move discussions/annotations to patient level"
```

---

### Task 21: Build frontend and deploy

**Files:**
- Modify: `frontend/vite.config.ts` (if needed)

- [ ] **Step 1: Build frontend**

Run: `cd /home/smudoshi/Github/Aurora/frontend && npm run build`
Expected: Build succeeds with no TypeScript errors.

- [ ] **Step 2: Fix any build errors**

If TypeScript errors, fix them. Common issues: missing imports, type mismatches from new props.

- [ ] **Step 3: Deploy to aurora.acumenus.net**

Run: `cd /home/smudoshi/Github/Aurora && bash deploy.sh`
Expected: Deployment succeeds.

- [ ] **Step 4: Verify deployment**

Open https://aurora.acumenus.net/profiles/{patientId} — Briefing should be default view.

- [ ] **Step 5: Final commit and push**

```bash
git add backend/public/build/ frontend/src/ backend/app/ backend/routes/ backend/database/
git commit -m "feat: complete action-oriented patient experience redesign"
git push origin v2/phase-0-scaffold
```

Note: Do NOT use `git add -A` — this would stage unrelated files (e.g., `backend/public/ohif/`). Only stage the directories containing changes from this plan.

---

## Summary

| Phase | Tasks | What It Delivers |
|-------|-------|-----------------|
| 1 | Tasks 1-11 | Backend schema + APIs + RecordRef validation + Briefing dashboard as default patient view |
| 2 | Tasks 12-15 | Inline action menus and select-and-act toolbar on all data views |
| 3 | Tasks 16-18 | Context-sensitive collaboration panel with 4 tabs |
| 4 | Tasks 19-22 | Enhanced session agenda, decision log, simplified case page, deploy |
