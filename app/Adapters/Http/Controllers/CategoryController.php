<?php

namespace App\Adapters\Http\Controllers;

use App\Application\Ports\CategoryRepository;

final class CategoryController
{
    /** GET /categories — the five categories ordered by name */
    public function index(CategoryRepository $categories)
    {
        return response()->json($categories->all());
    }
}
