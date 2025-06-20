<?php

namespace App\Http\Controllers;

use App\Models\ProductiveSubsidiary;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SubsidiaryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subsidiaries = ProductiveSubsidiary::with([
            'contactEntry',
            'customDomain',
            'defaultTaxRate',
            'integration',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/subsidiaries/index', [
            'subsidiaries' => [
                'data' => $subsidiaries->through(function ($subsidiary) {
                    return [
                        'id' => $subsidiary->id,
                        'name' => $subsidiary->name,
                        'contactEntry' => [
                            'name' => $subsidiary->contactEntry?->name,
                        ],
                        'customDomain' => [
                            'name' => $subsidiary->customDomain?->name,
                        ],
                        'defaultTaxRate' => [
                            'name' => $subsidiary->defaultTaxRate?->name,
                        ],
                        'integration' => [
                            'name' => $subsidiary->integration?->name,
                        ],
                        'archived_at' => $subsidiary->archived_at,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $subsidiaries->currentPage(),
                    'from' => $subsidiaries->firstItem(),
                    'last_page' => $subsidiaries->lastPage(),
                    'per_page' => $subsidiaries->perPage(),
                    'to' => $subsidiaries->lastItem(),
                    'total' => $subsidiaries->total(),
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
    public function show(ProductiveSubsidiary $productiveSubsidiary)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveSubsidiary $productiveSubsidiary)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveSubsidiary $productiveSubsidiary)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveSubsidiary $productiveSubsidiary)
    {
        //
    }
}
