<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductiveProject;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        return ProductiveProject::with('company')
            ->orderBy('name')
            ->get();
    }

    public function show(string $id)
    {
        return ProductiveProject::with('company')
            ->findOrFail($id);
    }
}
