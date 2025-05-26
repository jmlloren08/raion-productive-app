<?php

namespace App\Http\Controllers;

use App\Actions\Productive\GetSyncStatus;
use App\Actions\Productive\TriggerSync;
use App\Actions\Productive\GetRelationshipStats;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductiveSyncController extends Controller
{
    public function __construct(
        private GetSyncStatus $getSyncStatusAction,
        private TriggerSync $triggerSyncAction,
        private GetRelationshipStats $getRelationshipStatsAction
    ) {}

    /**
     * Display sync status and last sync time
     */
    public function status(): JsonResponse
    {
        try {
            $syncStatus = $this->getSyncStatusAction->handle();
            return response()->json($syncStatus);
        } catch (\Exception $e) {
            Log::error('Error getting sync status: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return response()->json([
                'message' => 'Failed to get sync status',
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Trigger a manual sync
     */
    public function sync(): JsonResponse
    {
        try {
            $syncResult = $this->triggerSyncAction->handle();
            return response()->json($syncResult, $syncResult['code'] ?? 200);
        } catch (\Exception $e) {
            Log::error('Error in sync controller: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return response()->json([
                'message' => 'Failed to run sync process',
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Get detailed stats about relationships between entities
     */    public function relationshipStats(): JsonResponse
    {
        try {
            $relationshipStats = $this->getRelationshipStatsAction->handle();
            return response()->json($relationshipStats);
        } catch (\Exception $e) {
            Log::error('Error getting relationship stats: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            return response()->json([
                'message' => 'Failed to get relationship statistics',
                'status' => 'error'
            ], 500);
        }
    }
}
