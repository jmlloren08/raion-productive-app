<?php

namespace App\Http\Controllers;

use App\Models\ProductiveInvoice;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $invoices = ProductiveInvoice::with([
            'billTo',
            'billFrom',
            'company',
            'documentType',
            'creator',
            'subsidiary',
            'parentInvoice',
            'issuer',
            'invoiceAttribution',
            'attachment',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/invoices/index', [
            'invoices' => [
                'data' => $invoices->through(function ($invoice) {
                    return [
                        'id' => $invoice->id,
                        'type' => $invoice->type,
                        'number' => $invoice->number,
                        'billTo' => [
                            'name' => $invoice->billTo?->name,
                        ],
                        'billFrom' => [
                            'name' => $invoice->billFrom?->name,
                        ],
                        'company' => [
                            'name' => $invoice->company?->name,
                        ],
                        'documentType' => [
                            'name' => $invoice->documentType?->name,
                        ],
                        'creator' => [
                            'first_name' => $invoice->creator?->first_name,
                        ],
                        'subsidiary' => [
                            'name' => $invoice->subsidiary?->name,
                        ],
                        'parentInvoice' => [
                            'number' => $invoice->parentInvoice?->number,
                        ],
                        'issuer' => [
                            'first_name' => $invoice->issuer?->first_name,
                        ],
                        'invoiceAttribution' => [
                            'amount' => $invoice->invoiceAttribution?->amount,
                        ],
                        'attachment' => [
                            'name' => $invoice->attachment?->name,
                        ],
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $invoices->currentPage(),
                    'from' => $invoices->firstItem(),
                    'last_page' => $invoices->lastPage(),
                    'per_page' => $invoices->perPage(),
                    'to' => $invoices->lastItem(),
                    'total' => $invoices->total(),
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
    public function show(ProductiveInvoice $productiveInvoice)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveInvoice $productiveInvoice)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveInvoice $productiveInvoice)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveInvoice $productiveInvoice)
    {
        //
    }
}
