<?php

namespace App\Http\Controllers;

use App\Models\ProductiveService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $services = ProductiveService::with([
            'serviceType',
            'deal',
            'person',
            'section',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/services/index', [
            'services' => [
                'data' => $services->through(function ($service) {
                    return [
                        'id' => $service->id,
                        'type' => $service->type,
                        'name' => $service->name,
                        'serviceType' => [
                            'name' => $service->serviceType?->name,
                        ],
                        'deal' => [
                            'name' => $service->deal?->name,
                        ],
                        'person' => [
                            'first_name' => $service->person?->first_name,
                        ],
                        'section' => [
                            'name' => $service->section?->name,
                        ],
                        'deleted_at_api' => $service->deleted_at_api,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $services->currentPage(),
                    'from' => $services->firstItem(),
                    'last_page' => $services->lastPage(),
                    'per_page' => $services->perPage(),
                    'to' => $services->lastItem(),
                    'total' => $services->total(),
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
    public function show(ProductiveService $productiveService)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveService $productiveService)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveService $productiveService)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveService $productiveService)
    {
        //
    }
}
