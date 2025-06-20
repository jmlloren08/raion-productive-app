<?php

namespace App\Http\Controllers;

use App\Models\ProductiveWorkflow;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WorkflowController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $workflows = ProductiveWorkflow::with([
            'workflowStatus',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/workflows/index', [
            'workflows' => [
                'data' => $workflows->through(function ($workflow) {
                    return [
                        'id' => $workflow->id,
                        'type' => $workflow->type,
                        'name' => $workflow->name,
                        'workflowStatus' => [
                            'name' => $workflow->workflowStatus?->name,
                        ],
                        'archived_at' => $workflow->archived_at,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $workflows->currentPage(),
                    'from' => $workflows->firstItem(),
                    'last_page' => $workflows->lastPage(),
                    'per_page' => $workflows->perPage(),
                    'to' => $workflows->lastItem(),
                    'total' => $workflows->total(),
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
