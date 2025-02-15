<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Models\ClinicalNote;
use App\Models\LabResult;
use App\Events\ClinicalDataUpdated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClinicalDocumentationController extends Controller
{
    public function storeNote(Request $request, Patient $patient)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:soap,progress,consult,procedure',
            'content' => 'required|array',
            'content.*' => 'required|string'
        ]);

        DB::beginTransaction();
        try {
            $note = new ClinicalNote([
                'patient_id' => $patient->id,
                'author_id' => Auth::id(),
                'type' => $validated['type'],
                'content' => $validated['content'],
                'status' => 'draft'
            ]);

            $note->save();

            // Create an audit trail entry
            $note->audits()->create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'metadata' => [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]
            ]);

            DB::commit();

            // Broadcast update to relevant team members
            broadcast(new ClinicalDataUpdated($patient->id, 'note', $note->id))->toOthers();

            return response()->json($note, 201);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function signNote(Request $request, ClinicalNote $note)
    {
        if ($note->status !== 'draft') {
            return response()->json(['error' => 'Note is not in draft status'], 400);
        }

        if (!Auth::user()->can('sign', $note)) {
            return response()->json(['error' => 'Unauthorized to sign note'], 403);
        }

        DB::beginTransaction();
        try {
            $note->status = 'signed';
            $note->signed_at = now();
            $note->signed_by = Auth::id();
            $note->save();

            $note->audits()->create([
                'user_id' => Auth::id(),
                'action' => 'signed',
                'metadata' => [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]
            ]);

            DB::commit();
            return response()->json($note);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function updateNote(Request $request, ClinicalNote $note)
    {
        if ($note->status === 'signed') {
            return response()->json(['error' => 'Cannot modify signed note'], 400);
        }

        $validated = $request->validate([
            'content' => 'required|array',
            'content.*' => 'required|string'
        ]);

        DB::beginTransaction();
        try {
            $originalContent = $note->content;
            $note->content = $validated['content'];
            $note->save();

            $note->audits()->create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'metadata' => [
                    'original_content' => $originalContent,
                    'new_content' => $validated['content'],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]
            ]);

            DB::commit();
            return response()->json($note);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getPatientMetrics(Patient $patient)
    {
        // Fetch vitals
        $vitals = $patient->vitals()
            ->orderBy('recorded_at', 'desc')
            ->take(20)
            ->get();

        // Fetch lab results
        $labs = $patient->labResults()
            ->with('labTest')
            ->orderBy('collected_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($lab) {
                return [
                    'id' => $lab->id,
                    'name' => $lab->labTest->name,
                    'category' => $lab->labTest->category,
                    'value' => $lab->value,
                    'unit' => $lab->unit,
                    'status' => $this->determineLabStatus($lab),
                    'timestamp' => $lab->collected_at
                ];
            });

        // Fetch medications
        $medications = $patient->medications()
            ->where('status', 'active')
            ->get();

        // Fetch outcomes
        $outcomes = $patient->outcomes()
            ->orderBy('recorded_at', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'vitals' => $vitals,
            'labs' => $labs,
            'medications' => $medications,
            'outcomes' => $outcomes
        ]);
    }

    private function determineLabStatus($lab)
    {
        if ($lab->value < $lab->labTest->reference_range_low ||
            $lab->value > $lab->labTest->reference_range_high) {
            return 'abnormal';
        }
        return 'normal';
    }
}

// app/Events/ClinicalDataUpdated.php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClinicalDataUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $patientId;
    public $dataType;
    public $dataId;

    public function __construct($patientId, $dataType, $dataId)
    {
        $this->patientId = $patientId;
        $this->dataType = $dataType;
        $this->dataId = $dataId;
    }

    public function broadcastOn()
    {
        return new Channel('patient.' . $this->patientId);
    }
}