<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductiveTimeEntries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimeEntryController extends Controller
{
    /**
     * Display a listing of time entries.
     */
    public function index(Request $request)
    {
        // Get query parameters for filtering
        $limit = $request->query('limit', 100);
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');
        $personId = $request->query('person_id');
        $taskId = $request->query('task_id');
        $serviceId = $request->query('service_id');
        $dealId = $request->query('deal_id');

        // Base query
        $query = ProductiveTimeEntries::query()
            ->orderBy('date', 'desc');

        // Apply filters if provided
        if ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }

        if ($personId) {
            $query->where('person_id', $personId);
        }

        if ($taskId) {
            $query->where('task_id', $taskId);
        }

        if ($serviceId) {
            $query->where('service_id', $serviceId);
        }

        if ($dealId) {
            $query->where('deal_id', $dealId);
        }

        // Calculate totals for summary stats
        $totalTime = $query->sum('time');
        $totalBillableTime = $query->sum('billable_time');
        
        // Get latest entries based on limit
        $timeEntries = $query->take($limit)->get();

        // Prepare summary statistics
        $summary = [
            'total_count' => $timeEntries->count(),
            'total_time' => $totalTime,
            'total_billable_time' => $totalBillableTime,
            'billable_percentage' => $totalTime > 0 ? round(($totalBillableTime / $totalTime) * 100, 2) : 0,
        ];

        // Group entries by date for visualization
        $entriesByDate = $timeEntries
            ->groupBy(function($entry) {
                return $entry->date->format('Y-m-d');
            })
            ->map(function ($entries) {
                return [
                    'total_time' => $entries->sum('time'),
                    'billable_time' => $entries->sum('billable_time'),
                    'count' => $entries->count()
                ];
            });

        return response()->json([
            'time_entries' => $timeEntries,
            'summary' => $summary,
            'by_date' => $entriesByDate
        ]);
    }

    /**
     * Display the specified time entry.
     */
    public function show(string $id)
    {
        $timeEntry = ProductiveTimeEntries::findOrFail($id);
        
        return response()->json([
            'time_entry' => $timeEntry
        ]);
    }
}
