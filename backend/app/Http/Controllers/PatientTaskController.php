<?php
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

        return ApiResponse::success(null, 'Deleted', 200);
    }
}
