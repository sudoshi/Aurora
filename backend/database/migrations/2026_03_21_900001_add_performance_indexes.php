<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Cases: compound indexes ─────────────────────────────────────────
        // Single-column indexes (status, specialty, urgency, created_by, patient_id, scheduled_at)
        // already exist from the create migration.

        Schema::table('app.cases', function (Blueprint $table) {
            try {
                $table->index(['status', 'specialty'], 'idx_cases_status_specialty');
            } catch (\Throwable) {
            }
            try {
                $table->index(['status', 'created_by'], 'idx_cases_status_created_by');
            } catch (\Throwable) {
            }
        });

        // ── Follow-ups: compound index ──────────────────────────────────────
        // Single-column indexes (status, due_date) already exist from the create migration.

        Schema::table('app.follow_ups', function (Blueprint $table) {
            try {
                $table->index(['assigned_to', 'status'], 'idx_follow_ups_assigned_status');
            } catch (\Throwable) {
            }
        });

        // ── Decisions: compound index ───────────────────────────────────────
        // Single-column indexes (case_id, status, urgency) already exist.

        Schema::table('app.decisions', function (Blueprint $table) {
            try {
                $table->index(['case_id', 'status'], 'idx_decisions_case_status');
            } catch (\Throwable) {
            }
        });

        // ── Session tables: indexes on FK / search columns ──────────────────
        // session_cases
        Schema::table('app.session_cases', function (Blueprint $table) {
            try {
                $table->index('session_id', 'idx_session_cases_session_id');
            } catch (\Throwable) {
            }
            try {
                $table->index('case_id', 'idx_session_cases_case_id');
            } catch (\Throwable) {
            }
            try {
                $table->index('status', 'idx_session_cases_status');
            } catch (\Throwable) {
            }
        });

        // session_participants
        Schema::table('app.session_participants', function (Blueprint $table) {
            try {
                $table->index('session_id', 'idx_session_participants_session_id');
            } catch (\Throwable) {
            }
            try {
                $table->index('user_id', 'idx_session_participants_user_id');
            } catch (\Throwable) {
            }
        });

        // ── Case sub-resources: FK indexes ──────────────────────────────────
        Schema::table('app.case_team_members', function (Blueprint $table) {
            try {
                $table->index('case_id', 'idx_case_team_members_case_id');
            } catch (\Throwable) {
            }
            try {
                $table->index('user_id', 'idx_case_team_members_user_id');
            } catch (\Throwable) {
            }
        });

        Schema::table('app.case_annotations', function (Blueprint $table) {
            try {
                $table->index('user_id', 'idx_case_annotations_user_id');
            } catch (\Throwable) {
            }
        });

        Schema::table('app.case_documents', function (Blueprint $table) {
            try {
                $table->index('uploaded_by', 'idx_case_documents_uploaded_by');
            } catch (\Throwable) {
            }
        });

        Schema::table('app.case_discussions', function (Blueprint $table) {
            try {
                $table->index('user_id', 'idx_case_discussions_user_id');
            } catch (\Throwable) {
            }
        });

        // ── Decision sub-resources ──────────────────────────────────────────
        Schema::table('app.decision_votes', function (Blueprint $table) {
            try {
                $table->index('decision_id', 'idx_decision_votes_decision_id');
            } catch (\Throwable) {
            }
            try {
                $table->index('user_id', 'idx_decision_votes_user_id');
            } catch (\Throwable) {
            }
        });

        Schema::table('app.follow_ups', function (Blueprint $table) {
            try {
                $table->index('assigned_to', 'idx_follow_ups_assigned_to');
            } catch (\Throwable) {
            }
        });
    }

    public function down(): void
    {
        // Cases compound indexes
        Schema::table('app.cases', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_cases_status_specialty');
            } catch (\Throwable) {
            }
            try {
                $table->dropIndex('idx_cases_status_created_by');
            } catch (\Throwable) {
            }
        });

        // Follow-ups compound index
        Schema::table('app.follow_ups', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_follow_ups_assigned_status');
            } catch (\Throwable) {
            }
            try {
                $table->dropIndex('idx_follow_ups_assigned_to');
            } catch (\Throwable) {
            }
        });

        // Decisions compound index
        Schema::table('app.decisions', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_decisions_case_status');
            } catch (\Throwable) {
            }
        });

        // Session sub-table indexes
        Schema::table('app.session_cases', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_session_cases_session_id');
            } catch (\Throwable) {
            }
            try {
                $table->dropIndex('idx_session_cases_case_id');
            } catch (\Throwable) {
            }
            try {
                $table->dropIndex('idx_session_cases_status');
            } catch (\Throwable) {
            }
        });

        Schema::table('app.session_participants', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_session_participants_session_id');
            } catch (\Throwable) {
            }
            try {
                $table->dropIndex('idx_session_participants_user_id');
            } catch (\Throwable) {
            }
        });

        // Case sub-resources
        Schema::table('app.case_team_members', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_case_team_members_case_id');
            } catch (\Throwable) {
            }
            try {
                $table->dropIndex('idx_case_team_members_user_id');
            } catch (\Throwable) {
            }
        });

        Schema::table('app.case_annotations', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_case_annotations_user_id');
            } catch (\Throwable) {
            }
        });

        Schema::table('app.case_documents', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_case_documents_uploaded_by');
            } catch (\Throwable) {
            }
        });

        Schema::table('app.case_discussions', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_case_discussions_user_id');
            } catch (\Throwable) {
            }
        });

        // Decision sub-resources
        Schema::table('app.decision_votes', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_decision_votes_decision_id');
            } catch (\Throwable) {
            }
            try {
                $table->dropIndex('idx_decision_votes_user_id');
            } catch (\Throwable) {
            }
        });
    }
};
