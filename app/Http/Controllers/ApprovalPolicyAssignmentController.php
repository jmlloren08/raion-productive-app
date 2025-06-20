<?php

namespace App\Http\Controllers;

use App\Models\ProductiveApa;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApprovalPolicyAssignmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $approvalPolicyAssignments = ProductiveApa::with([
            'person',
            'deal',
            'approvalPolicy'
        ])->latest()->paginate(10);

        return Inertia::render('datasets/approval-policy-assignments/index', [
            'approvalPolicyAssignments' => [
                'data' => $approvalPolicyAssignments->through(function ($assignment) {
                    return [
                        'id' => $assignment->id,
                        'type' => $assignment->type,
                        'target_type' => $assignment->target_type,
                        'person' => [
                            'name' => $assignment->person?->name,
                        ],
                        'deal' => [
                            'name' => $assignment->deal?->name,
                        ],
                        'approvalPolicy' => [
                            'name' => $assignment->approvalPolicy?->name,
                        ],
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $approvalPolicyAssignments->currentPage(),
                    'from' => $approvalPolicyAssignments->firstItem(),
                    'last_page' => $approvalPolicyAssignments->lastPage(),
                    'per_page' => $approvalPolicyAssignments->perPage(),
                    'to' => $approvalPolicyAssignments->lastItem(),
                    'total' => $approvalPolicyAssignments->total(),
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
    public function show(ProductiveApa $productiveApa)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveApa $productiveApa)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveApa $productiveApa)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveApa $productiveApa)
    {
        //
    }
}
