<?php

namespace App\Http\Controllers;

use App\Models\ProductiveTaxRate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TaxRateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $taxRates = ProductiveTaxRate::with([
            'subsidiary',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/tax-rates/index', [
            'taxRates' => [
                'data' => $taxRates->through(function ($taxRate) {
                    return [
                        'id' => $taxRate->id,
                        'type' => $taxRate->type,
                        'name' => $taxRate->name,
                        'subsidiary' => [
                            'name' => $taxRate->subsidiary?->name,
                        ],
                        'archived_at' => $taxRate->archived_at,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $taxRates->currentPage(),
                    'from' => $taxRates->firstItem(),
                    'last_page' => $taxRates->lastPage(),
                    'per_page' => $taxRates->perPage(),
                    'to' => $taxRates->lastItem(),
                    'total' => $taxRates->total(),
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
    public function show(ProductiveTaxRate $productiveTaxRate)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveTaxRate $productiveTaxRate)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveTaxRate $productiveTaxRate)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveTaxRate $productiveTaxRate)
    {
        //
    }
}
