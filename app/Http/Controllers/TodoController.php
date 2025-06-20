<?php

namespace App\Http\Controllers;

use App\Models\ProductiveTodo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TodoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $todos = ProductiveTodo::with([
            'assignee',
            'deal',
            'task',
        ])->latest()->paginate(10);

        return Inertia::render('datasets/todos/index', [
            'todos' => [
                'data' => $todos->through(function ($todo) {
                    return [
                        'id' => $todo->id,
                        'type' => $todo->type,
                        'description' => $todo->description,
                        'assignee' => [
                            'first_name' => $todo->assignee?->first_name,
                        ],
                        'deal' => [
                            'name' => $todo->deal?->name,
                        ],
                        'task' => [
                            'title' => $todo->task?->title,
                        ],
                        'created_at_api' => $todo->created_at_api,
                    ];
                })->toArray(),
                'meta' => [
                    'current_page' => $todos->currentPage(),
                    'from' => $todos->firstItem(),
                    'last_page' => $todos->lastPage(),
                    'per_page' => $todos->perPage(),
                    'to' => $todos->lastItem(),
                    'total' => $todos->total(),
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
    public function show(ProductiveTodo $productiveTodo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductiveTodo $productiveTodo)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductiveTodo $productiveTodo)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductiveTodo $productiveTodo)
    {
        //
    }
}
