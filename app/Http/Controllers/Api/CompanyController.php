<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductiveCompany;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index()
    {
        return ProductiveCompany::with('projects')
            ->orderBy('name')
            ->get();
    }

    public function show(string $id)
    {
        return ProductiveCompany::with('projects')
            ->findOrFail($id);
    }
}
