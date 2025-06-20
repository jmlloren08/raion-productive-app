<?php

namespace App\Http\Controllers;

use App\Models\ProductiveInvoiceAttribution;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceAttributionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $invoiceAttributions = ProductiveInvoiceAttribution::with([
            'invoice',
            'budget',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/invoice-attributions/index', [
            'invoiceAttributions' => [
                'data' => $invoiceAttributions->through(function ($attribution) {
                    return [
                        'id' => $attribution->id,
                        'type' => $attribution->type,
                        'amount' => $attribution->amount,
                        'invoice' => [
                            'number' => $attribution->invoice?->number,
                        ],
                        'budget' => [
                            'name' => $attribution->budget?->name,
                        ],
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $invoiceAttributions->currentPage(),
                    'from' => $invoiceAttributions->firstItem(),
                    'last_page' => $invoiceAttributions->lastPage(),
                    'per_page' => $invoiceAttributions->perPage(),
                    'to' => $invoiceAttributions->lastItem(),
                    'total' => $invoiceAttributions->total(),
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
    public function show(ProductiveInvoiceAttribution $productiveInvoiceAttribution)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveInvoiceAttribution $productiveInvoiceAttribution)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveInvoiceAttribution $productiveInvoiceAttribution)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveInvoiceAttribution $productiveInvoiceAttribution)
    {
        //
    }
}
