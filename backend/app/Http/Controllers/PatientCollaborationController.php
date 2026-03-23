<?php

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

    public function decisions(int $patient): JsonResponse
    {
        $patientModel = ClinicalPatient::findOrFail($patient);

        $decisions = $patientModel->decisions()
            ->with(['proposer:id,name', 'votes', 'followUps', 'clinicalCase:id,title', 'session:id,title'])
            ->orderByDesc('created_at')
            ->get();

        return ApiResponse::success($decisions);
    }
}
