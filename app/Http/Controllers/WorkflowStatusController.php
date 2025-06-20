<?php

namespace App\Http\Controllers;

use App\Models\ProductiveWorkflow;
use App\Models\ProductiveWorkflowStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WorkflowStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $workflowStatuses = ProductiveWorkflowStatus::with([
            'workflow',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/workflow-statuses/index', [
            'workflowStatuses' => [
                'data' => $workflowStatuses->through(function ($workflowStatus) {
                    return [
                        'id' => $workflowStatus->id,
                        'name' => $workflowStatus->name,
                        'color_id' => $workflowStatus->color_id,
                        'position' => $workflowStatus->position,
                        'category_id' => $workflowStatus->category_id,
                        'workflow' => [
                            'name' => $workflowStatus->workflow?->name,
                        ],
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $workflowStatuses->currentPage(),
                    'from' => $workflowStatuses->firstItem(),
                    'last_page' => $workflowStatuses->lastPage(),
                    'per_page' => $workflowStatuses->perPage(),
                    'to' => $workflowStatuses->lastItem(),
                    'total' => $workflowStatuses->total(),
                ],
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductiveWorkflow $productiveWorkflow)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveWorkflow $productiveWorkflow)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveWorkflow $productiveWorkflow)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveWorkflow $productiveWorkflow)
    {
        //
    }
}
