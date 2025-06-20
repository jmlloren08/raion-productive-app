<?php

namespace App\Http\Controllers;

use App\Models\ProductivePaymentReminder;
use Illuminate\Http\Request;

class PaymentReminderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $paymentReminders = ProductivePaymentReminder::with([
            'creator',
            'updater',
            'invoice',
            'prs',
        ])->latest()->paginate(10);

        return inertia('datasets/payment-reminders/index', [
            'paymentReminders' => [
                'data' => $paymentReminders->through(function ($reminder) {
                    return [
                        'id' => $reminder->id,
                        'type' => $reminder->type,
                        'subject' => $reminder->subject,
                        'creator' => [
                            'first_name' => $reminder->creator?->first_name,
                        ],
                        'updater' => [
                            'first_name' => $reminder->updater?->first_name,
                        ],
                        'invoice' => [
                            'number' => $reminder->invoice?->number,
                        ],
                        'prs' => [
                            'name' => $reminder->prs?->name,
                        ],
                        'created_at_api' => $reminder->created_at_api,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $paymentReminders->currentPage(),
                    'from' => $paymentReminders->firstItem(),
                    'last_page' => $paymentReminders->lastPage(),
                    'per_page' => $paymentReminders->perPage(),
                    'to' => $paymentReminders->lastItem(),
                    'total' => $paymentReminders->total(),
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
    public function show(ProductivePaymentReminder $productivePaymentReminder)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductivePaymentReminder $productivePaymentReminder)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductivePaymentReminder $productivePaymentReminder)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductivePaymentReminder $productivePaymentReminder)
    {
        //
    }
}
