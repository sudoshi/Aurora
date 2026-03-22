<?php

namespace App\Http\Controllers;

use App\Http\Helpers\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats(Request $request)
    {
        $userId = $request->user()->id;

        // Count patients in clinical schema
        $totalPatients = DB::table('clinical.patients')->count();

        // Count cases (the cases table may not exist yet, so handle gracefully)
        $totalCases = 0;
        $activeCases = 0;
        $recentCases = [];
        $pendingDecisions = 0;

        try {
            $totalCases = DB::table('app.cases')->whereNull('deleted_at')->count();
            $activeCases = DB::table('app.cases')
                ->whereNull('deleted_at')
                ->where('status', 'active')
                ->count();

            $recentCases = DB::table('app.cases')
                ->join('app.users', 'app.cases.created_by', '=', 'app.users.id')
                ->whereNull('app.cases.deleted_at')
                ->orderBy('app.cases.created_at', 'desc')
                ->limit(10)
                ->select([
                    'app.cases.id',
                    'app.cases.title',
                    'app.cases.specialty',
                    'app.cases.urgency',
                    'app.cases.status',
                    'app.cases.case_type',
                    'app.cases.created_at',
                    'app.users.name as creator_name',
                ])
                ->get();

            $pendingDecisions = DB::table('app.decisions')
                ->where('status', 'proposed')
                ->count();
        } catch (\Exception $e) {
            // Tables may not exist yet — that's fine
        }

        // Active users (logged in within last 7 days)
        $activeUsers = User::where('last_login_at', '>=', now()->subDays(7))->count();
        $totalUsers = User::count();

        // System health
        $systemHealth = [
            'database' => 'healthy',
            'cache' => 'healthy',
        ];

        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            $systemHealth['database'] = 'unavailable';
        }

        try {
            cache()->put('health_check', true, 5);
            cache()->get('health_check');
        } catch (\Exception $e) {
            $systemHealth['cache'] = 'unavailable';
        }

        return ApiResponse::success([
            'total_patients' => $totalPatients,
            'total_cases' => $totalCases,
            'active_cases' => $activeCases,
            'active_users' => $activeUsers,
            'total_users' => $totalUsers,
            'pending_decisions' => $pendingDecisions,
            'recent_cases' => $recentCases,
            'system_health' => $systemHealth,
        ]);
    }
}
