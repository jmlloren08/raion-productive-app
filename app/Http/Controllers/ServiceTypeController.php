<?php

namespace App\Http\Controllers;

use App\Models\ProductiveServiceType;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $serviceTypes = ProductiveServiceType::with([
            'assignee',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/service-types/index', [
            'serviceTypes' => [
                'data' => $serviceTypes->through(function ($serviceType) {
                    return [
                        'id' => $serviceType->id,
                        'type' => $serviceType->type,
                        'name' => $serviceType->name,
                        'assignee' => [
                            'first_name' => $serviceType->assignee?->first_name,
                        ],
                        'archived_at_api' => $serviceType->archived_at_api,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $serviceTypes->currentPage(),
                    'from' => $serviceTypes->firstItem(),
                    'last_page' => $serviceTypes->lastPage(),
                    'per_page' => $serviceTypes->perPage(),
                    'to' => $serviceTypes->lastItem(),
                    'total' => $serviceTypes->total(),
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
    public function show(ProductiveServiceType $productiveServiceType)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveServiceType $productiveServiceType)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveServiceType $productiveServiceType)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveServiceType $productiveServiceType)
    {
        //
    }
}
