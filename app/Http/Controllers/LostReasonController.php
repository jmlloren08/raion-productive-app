<?php

namespace App\Http\Controllers;

use App\Models\ProductiveLostReason;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LostReasonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $lostReasons = ProductiveLostReason::latest()->paginate(10);

        return Inertia::render('datasets/lost-reasons/index', [
            'lostReasons' => [
                'data' => $lostReasons->through(function ($lostReason) {
                    return [
                        'id' => $lostReason->id,
                        'type' => $lostReason->type,
                        'name' => $lostReason->name,
                        'archived_at' => $lostReason->archived_at,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $lostReasons->currentPage(),
                    'from' => $lostReasons->firstItem(),
                    'last_page' => $lostReasons->lastPage(),
                    'per_page' => $lostReasons->perPage(),
                    'to' => $lostReasons->lastItem(),
                    'total' => $lostReasons->total(),
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
    public function show(ProductiveLostReason $productiveLostReason)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveLostReason $productiveLostReason)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveLostReason $productiveLostReason)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveLostReason $productiveLostReason)
    {
        //
    }
}
