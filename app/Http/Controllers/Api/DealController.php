<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductiveDeal;
use Illuminate\Http\Request;

class DealController extends Controller
{
    public function index()
    {
        $deals = ProductiveDeal::with(['company', 'project'])->get();
        
        return response()->json($deals);
    }

    public function show($id)
    {
        $deal = ProductiveDeal::with(['company', 'project'])->findOrFail($id);
        
        return response()->json($deal);
    }
}
