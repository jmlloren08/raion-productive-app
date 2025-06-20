<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ProductiveCompany;
use Inertia\Inertia;

class CompanyController extends Controller
{
    public function index()
    {
        $companies = ProductiveCompany::with([
            'subsidiary',
            'taxRate'
        ])->latest()->paginate(10);

        return Inertia::render('datasets/companies/index', [
            'companies' => [
                'data' => $companies->through(function ($company) {
                    return [
                        'id' => $company->id,
                        'type' => $company->type,
                        'name' => $company->name,
                        'billing_name' => $company->billing_name,
                        'subsidiary' => [
                            'name' => $company->subsidiary?->name,
                        ],
                        'taxRate' => [
                            'name' => $company->taxRate?->name,
                        ],
                        'created_at_api' => $company->created_at_api,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $companies->currentPage(),
                    'from' => $companies->firstItem(),
                    'last_page' => $companies->lastPage(),
                    'per_page' => $companies->perPage(),
                    'to' => $companies->lastItem(),
                    'total' => $companies->total(),
                ],
            ],
        ]);
    }

    public function show(string $id)
    {
        return ProductiveCompany::with('projects')
            ->findOrFail($id);
    }
}
