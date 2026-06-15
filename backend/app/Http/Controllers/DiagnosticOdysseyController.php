<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Http\Requests\StoreOdysseyRequest;
use App\Http\Requests\TransitionOdysseyRequest;
use App\Models\Clinical\ClinicalPatient;
use App\Models\DiagnosticOdyssey;
use App\Services\RareDisease\InvalidOdysseyTransitionException;
use App\Services\RareDisease\InvalidPhenopacketException;
use App\Services\RareDisease\OdysseyService;
use App\Services\RareDisease\OdysseyStateMachine;
use App\Services\RareDisease\PhenopacketExporter;
use App\Services\RareDisease\PhenopacketImporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiagnosticOdysseyController extends Controller
{
    public function __construct(
        private OdysseyService $service,
        private OdysseyStateMachine $machine,
        private PhenopacketExporter $exporter,
        private PhenopacketImporter $importer,
    ) {}

    public function index(int $patient): JsonResponse
    {
        $patientModel = ClinicalPatient::findOrFail($patient);

        $odysseys = $patientModel->odysseys()
            ->withCount('phenotypeFeatures')
            ->orderByDesc('created_at')
            ->get();

        return ApiResponse::success($odysseys);
    }

    public function store(StoreOdysseyRequest $request, int $patient): JsonResponse
    {
        $patientModel = ClinicalPatient::findOrFail($patient);

        $odyssey = $this->service->create([
            ...$request->validated(),
            'patient_id' => $patientModel->id,
        ], $request->user()->id);

        return ApiResponse::success($odyssey->load('transitions'), 'Created', 201);
    }

    public function show(int $odyssey): JsonResponse
    {
        $model = DiagnosticOdyssey::with(['transitions.actor:id,name', 'phenotypeFeatures'])
            ->findOrFail($odyssey);

        return ApiResponse::success([
            'odyssey' => $model,
            'allowed_transitions' => $this->machine->allowedFrom($model->status),
        ]);
    }

    public function transition(TransitionOdysseyRequest $request, int $odyssey): JsonResponse
    {
        $model = DiagnosticOdyssey::findOrFail($odyssey);

        try {
            $updated = $this->service->transition(
                $model,
                $request->validated()['to_status'],
                $request->user()->id,
                $request->validated()['note'] ?? null,
            );
        } catch (InvalidOdysseyTransitionException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($updated);
    }

    public function phenopacket(int $odyssey): JsonResponse
    {
        $model = DiagnosticOdyssey::with('phenotypeFeatures')->findOrFail($odyssey);

        return ApiResponse::success($this->exporter->export($model));
    }

    public function worklist(Request $request): JsonResponse
    {
        $query = DiagnosticOdyssey::query()
            ->with('patient:id,first_name,last_name,mrn')
            ->withCount('phenotypeFeatures')
            ->orderByDesc('updated_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('progress_status')) {
            $query->where('progress_status', $request->string('progress_status'));
        }

        return ApiResponse::paginated($query->paginate($request->integer('per_page', 25)));
    }

    public function importPhenopacket(Request $request, int $odyssey): JsonResponse
    {
        $model = DiagnosticOdyssey::findOrFail($odyssey);

        try {
            $result = $this->importer->importInto($model, $request->all(), $request->user()->id);
        } catch (InvalidPhenopacketException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        return ApiResponse::success($result);
    }
}
