<?php

namespace App\Http\Controllers;

use App\Models\ProductiveDealStatus;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DealStatusController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $dealStatuses = ProductiveDealStatus::with([
            'pipeline',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/deal-statuses/index', [
            'dealStatuses' => [
                'data' => $dealStatuses->through(function ($status) {
                    return [
                        'id' => $status->id,
                        'type' => $status->type,
                        'name' => $status->name,
                        'pipeline' => [
                            'name' => $status->pipeline?->name,
                        ],
                        'archived_at' => $status->archived_at,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $dealStatuses->currentPage(),
                    'from' => $dealStatuses->firstItem(),
                    'last_page' => $dealStatuses->lastPage(),
                    'per_page' => $dealStatuses->perPage(),
                    'to' => $dealStatuses->lastItem(),
                    'total' => $dealStatuses->total(),
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
    public function show(ProductiveDealStatus $productiveDealStatus)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveDealStatus $productiveDealStatus)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveDealStatus $productiveDealStatus)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveDealStatus $productiveDealStatus)
    {
        //
    }
}
