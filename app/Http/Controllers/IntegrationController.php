<?php

namespace App\Http\Controllers;

use App\Models\ProductiveIntegration;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IntegrationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $integrations = ProductiveIntegration::with([
            'subsidiary',
            'project',
            'creator',
            'deal',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/integrations/index', [
            'integrations' => [
                'data' => $integrations->through(function ($integration) {
                    return [
                        'id' => $integration->id,
                        'type' => $integration->type,
                        'name' => $integration->name,
                        'subsidiary' => [
                            'name' => $integration->subsidiary?->name,
                        ],
                        'project' => [
                            'name' => $integration->project?->name,
                        ],
                        'creator' => [
                            'first_name' => $integration->creator?->first_name,
                        ],
                        'deal' => [
                            'name' => $integration->deal?->name,
                        ],
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $integrations->currentPage(),
                    'from' => $integrations->firstItem(),
                    'last_page' => $integrations->lastPage(),
                    'per_page' => $integrations->perPage(),
                    'to' => $integrations->lastItem(),
                    'total' => $integrations->total(),
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
    public function show(ProductiveIntegration $productiveIntegration)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveIntegration $productiveIntegration)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveIntegration $productiveIntegration)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveIntegration $productiveIntegration)
    {
        //
    }
}
