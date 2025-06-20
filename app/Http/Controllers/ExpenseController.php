<?php

namespace App\Http\Controllers;

use App\Models\ProductiveExpense;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $expenses = ProductiveExpense::with([
            'deal',
            'serviceType',
            'person',
            'creator',
            'approver',
            'rejecter',
            'service',
            'purchaseOrder',
            'taxRate',
            'attachment',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/expenses/index', [
            'expenses' => [
                'data' => $expenses->through(function ($expense) {
                    return [
                        'id' => $expense->id,
                        'type' => $expense->type,
                        'name' => $expense->name,
                        'deal' => [
                            'name' => $expense->deal?->name,
                        ],
                        'serviceType' => [
                            'name' => $expense->serviceType?->name,
                        ],
                        'person' => [
                            'first_name' => $expense->person?->first_name,
                        ],
                        'creator' => [
                            'first_name' => $expense->creator?->first_name,
                        ],
                        'approver' => [
                            'first_name' => $expense->approver?->first_name,
                        ],
                        'rejecter' => [
                            'first_name' => $expense->rejecter?->first_name,
                        ],
                        'service' => [
                            'name' => $expense->service?->name,
                        ],
                        'purchaseOrder' => [
                            'subject' => $expense->purchaseOrder?->subject,
                        ],
                        'taxRate' => [
                            'name' => $expense->taxRate?->name,
                        ],
                        'attachment' => [
                            'name' => $expense->attachment?->name,
                        ],
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $expenses->currentPage(),
                    'from' => $expenses->firstItem(),
                    'last_page' => $expenses->lastPage(),
                    'per_page' => $expenses->perPage(),
                    'to' => $expenses->lastItem(),
                    'total' => $expenses->total(),
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
    public function show(ProductiveExpense $productiveExpense)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveExpense $productiveExpense)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveExpense $productiveExpense)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveExpense $productiveExpense)
    {
        //
    }
}
