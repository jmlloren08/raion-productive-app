<?php

namespace App\Http\Controllers;

use App\Models\ProductiveBill;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $bills = ProductiveBill::with([
            'purchaseOrder',
            'creator',
            'deal',
            'attachment'
        ])->latest()->paginate(10);

        return Inertia::render('datasets/bills/index', [
            'bills' => [
                'data' => $bills->through(function ($bill) {
                    return [
                        'id' => $bill->id,
                        'type' => $bill->type,
                        'description' => $bill->description,
                        'purchaseOrder' => [
                            'subject' => $bill->purchaseOrder?->subject,
                        ],
                        'creator' => [
                            'first_name' => $bill->creator?->first_name,
                        ],
                        'deal' => [
                            'name' => $bill->deal?->name,
                        ],
                        'attachment' => [
                            'name' => $bill->attachment?->name,
                        ],
                        'created_at_api' => $bill->created_at_api,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $bills->currentPage(),
                    'from' => $bills->firstItem(),
                    'last_page' => $bills->lastPage(),
                    'per_page' => $bills->perPage(),
                    'to' => $bills->lastItem(),
                    'total' => $bills->total(),
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
    public function show(ProductiveBill $productiveBill)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveBill $productiveBill)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveBill $productiveBill)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveBill $productiveBill)
    {
        //
    }
}
