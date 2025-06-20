<?php

namespace App\Http\Controllers;

use App\Models\ProductivePrs;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PrsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $paymentReminderSequences = ProductivePrs::with([
            'creator',
            'updater',
            'paymentReminder',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/payment-reminder-sequences/index', [
            'paymentReminderSequences' => [
                'data' => $paymentReminderSequences->through(function ($prs) {
                    return [
                        'id' => $prs->id,
                        'type' => $prs->type,
                        'name' => $prs->name,
                        'creator' => [
                            'first_name' => $prs->creator?->first_name,
                        ],
                        'updater' => [
                            'first_name' => $prs->updater?->first_name,
                        ],
                        'paymentReminder' => [
                            'subject' => $prs->paymentReminder?->subject,
                        ],
                        'created_at_api' => $prs->created_at_api,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $paymentReminderSequences->currentPage(),
                    'from' => $paymentReminderSequences->firstItem(),
                    'last_page' => $paymentReminderSequences->lastPage(),
                    'per_page' => $paymentReminderSequences->perPage(),
                    'to' => $paymentReminderSequences->lastItem(),
                    'total' => $paymentReminderSequences->total(),
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
    public function show(ProductivePrs $productivePrs)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductivePrs $productivePrs)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductivePrs $productivePrs)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductivePrs $productivePrs)
    {
        //
    }
}
