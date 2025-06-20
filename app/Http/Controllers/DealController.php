<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProductiveDeal;
use Inertia\Inertia;

class DealController extends Controller
{
    public function index()
    {
        $deals = ProductiveDeal::with([
            'creator',
            'company',
            'documentType',
            'responsible',
            'dealStatus',
            'project',
            'lostReason',
            'contract',
            'contact',
            'subsidiary',
            'taxRate',
            'apa',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/deals/index', [
            'deals' => [
                'data' => $deals->through(function ($deal) {
                    return [
                        'id' => $deal->id,
                        'type' => $deal->type,
                        'name' => $deal->name,
                        'creator' => [
                            'first_name' => $deal->creator?->first_name,
                        ],
                        'company' => [
                            'name' => $deal->company?->name,
                        ],
                        'documentType' => [
                            'name' => $deal->documentType?->name,
                        ],
                        'responsible' => [
                            'first_name' => $deal->responsible?->first_name,
                        ],
                        'dealStatus' => [
                            'name' => $deal->dealStatus?->name,
                        ],
                        'project' => [
                            'name' => $deal->project?->name,
                        ],
                        'lostReason' => [
                            'name' => $deal->lostReason?->name,
                        ],
                        'contract' => [
                            'ends_on' => $deal->contract?->ends_on,
                        ],
                        'contact' => [
                            'name' => $deal->contact?->name,
                        ],
                        'subsidiary' => [
                            'name' => $deal->subsidiary?->name,
                        ],
                        'taxRate' => [
                            'name' => $deal->taxRate?->name,
                        ],
                        'apa' => [
                            'target_type' => $deal->apa?->target_type,
                        ],
                        'created_at_api' => $deal->created_at_api,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => 1, // Assuming no pagination for simplicity
                    'from' => 1,
                    'last_page' => 1,
                    'per_page' => count($deals),
                    'to' => count($deals),
                    'total' => count($deals),
                ],
            ],
        ]);
    }

    public function show($id)
    {
        $deal = ProductiveDeal::with(['company', 'project'])->findOrFail($id);

        return response()->json($deal);
    }
}
