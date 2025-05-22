<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductiveTimeEntryVersions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimeEntryVersionController extends Controller
{
    /**
     * Display a listing of time entry versions.
     */
    public function index(Request $request)
    {
        // Get query parameters for filtering
        $limit = $request->query('limit', 100);
        $timeEntryId = $request->query('time_entry_id');
        $event = $request->query('event');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        // Base query
        $query = ProductiveTimeEntryVersions::query()
            ->orderBy('created_at_api', 'desc');

        // Apply filters if provided
        if ($timeEntryId) {
            $query->where('item_id', $timeEntryId);
        }

        if ($event) {
            $query->where('event', $event);
        }

        if ($dateFrom) {
            $query->where('created_at_api', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->where('created_at_api', '<=', $dateTo);
        }

        // Get the total count for the query
        $totalCount = $query->count();
        
        // Get versions based on limit
        $timeEntryVersions = $query->take($limit)->get();

        // Prepare summary statistics
        $summary = [
            'total_count' => $totalCount,
            'displayed_count' => $timeEntryVersions->count(),
        ];

        // Group versions by event type for visualization
        $versionsByEvent = $timeEntryVersions
            ->groupBy('event')
            ->map(function ($versions) {
                return [
                    'count' => $versions->count()
                ];
            });

        // Group versions by date for timeline visualization
        $versionsByDate = $timeEntryVersions
            ->groupBy(function($version) {
                return date('Y-m-d', strtotime($version->created_at_api));
            })
            ->map(function ($versions) {
                return [
                    'count' => $versions->count(),
                    'by_event' => $versions->groupBy('event')
                        ->map(function ($eventVersions) {
                            return $eventVersions->count();
                        })
                ];
            });

        return response()->json([
            'time_entry_versions' => $timeEntryVersions,
            'summary' => $summary,
            'by_event' => $versionsByEvent,
            'by_date' => $versionsByDate
        ]);
    }

    /**
     * Display the specified time entry version.
     */
    public function show(string $id)
    {
        $timeEntryVersion = ProductiveTimeEntryVersions::findOrFail($id);
        
        return response()->json([
            'time_entry_version' => $timeEntryVersion
        ]);
    }
    
    /**
     * Get history for a specific time entry by ID.
     */
    public function history(string $timeEntryId)
    {
        $versions = ProductiveTimeEntryVersions::where('item_id', $timeEntryId)
            ->orderBy('created_at_api', 'desc')
            ->get();
            
        $timelineData = $versions->map(function ($version) {
            return [
                'id' => $version->id,
                'event' => $version->event,
                'date' => $version->created_at_api,
                'changes' => $version->object_changes,
                'creator_id' => $version->creator_id
            ];
        });
        
        return response()->json([
            'time_entry_id' => $timeEntryId,
            'version_count' => $versions->count(),
            'timeline' => $timelineData
        ]);
    }
}
