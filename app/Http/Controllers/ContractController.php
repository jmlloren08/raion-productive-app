<?php

namespace App\Http\Controllers;

use App\Models\ProductiveContract;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contracts = ProductiveContract::with([
            'deal',
        ])->latest()->paginate(10);

        return inertia('datasets/contracts/index', [
            'contracts' => [
                'data' => $contracts->through(function ($contract) {
                    return [
                        'id' => $contract->id,
                        'type' => $contract->type,
                        'ends_on' => $contract->ends_on,
                        'starts_on' => $contract->starts_on,
                        'deal' => [
                            'name' => $contract->deal?->name,
                        ],
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $contracts->currentPage(),
                    'from' => $contracts->firstItem(),
                    'last_page' => $contracts->lastPage(),
                    'per_page' => $contracts->perPage(),
                    'to' => $contracts->lastItem(),
                    'total' => $contracts->total(),
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
    public function show(ProductiveContract $productiveContract)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveContract $productiveContract)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveContract $productiveContract)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveContract $productiveContract)
    {
        //
    }
}
