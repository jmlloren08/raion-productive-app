<?php

namespace App\Http\Controllers;

use App\Models\ProductiveApprovalPolicy;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApprovalPolicyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $approvalPolicies = ProductiveApprovalPolicy::latest()->paginate(10);

        return Inertia::render('datasets/approval-policies/index', [
            'approvalPolicies' => [
                'data' => $approvalPolicies->through(function ($policy) {
                    return [
                        'id' => $policy->id,
                        'type' => $policy->type,
                        'name' => $policy->name,
                        'description' => $policy->description,
                        'custom' => $policy->custom,
                        'default' => $policy->default,
                        'archived_at' => $policy->archived_at,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $approvalPolicies->currentPage(),
                    'from' => $approvalPolicies->firstItem(),
                    'last_page' => $approvalPolicies->lastPage(),
                    'per_page' => $approvalPolicies->perPage(),
                    'to' => $approvalPolicies->lastItem(),
                    'total' => $approvalPolicies->total(),
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
    public function show(ProductiveApprovalPolicy $productiveApprovalPolicy)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveApprovalPolicy $productiveApprovalPolicy)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveApprovalPolicy $productiveApprovalPolicy)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveApprovalPolicy $productiveApprovalPolicy)
    {
        //
    }
}
