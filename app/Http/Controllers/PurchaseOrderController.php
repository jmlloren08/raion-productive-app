<?php

namespace App\Http\Controllers;

use App\Models\ProductivePurchaseOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $purchaseOrders = ProductivePurchaseOrder::with([
            'deal',
            'creator',
            'documentType',
            'attachment',
            'billTo',
            'billFrom',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/purchase-orders/index', [
            'purchaseOrders' => [
                'data' => $purchaseOrders->through(function ($order) {
                    return [
                        'id' => $order->id,
                        'type' => $order->type,
                        'subject' => $order->subject,
                        'deal' => [
                            'name' => $order->deal?->name,
                        ],
                        'creator' => [
                            'first_name' => $order->creator?->first_name,
                        ],
                        'documentType' => [
                            'name' => $order->documentType?->name,
                        ],
                        'attachment' => [
                            'name' => $order->attachment?->name,
                        ],
                        'billTo' => [
                            'name' => $order->billTo?->name,
                        ],
                        'billFrom' => [
                            'name' => $order->billFrom?->name,
                        ],
                        'created_at_api' => $order->created_at_api,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $purchaseOrders->currentPage(),
                    'from' => $purchaseOrders->firstItem(),
                    'last_page' => $purchaseOrders->lastPage(),
                    'per_page' => $purchaseOrders->perPage(),
                    'to' => $purchaseOrders->lastItem(),
                    'total' => $purchaseOrders->total(),
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
    public function show(ProductivePurchaseOrder $productivePurchaseOrder)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductivePurchaseOrder $productivePurchaseOrder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductivePurchaseOrder $productivePurchaseOrder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductivePurchaseOrder $productivePurchaseOrder)
    {
        //
    }
}
