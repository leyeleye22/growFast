<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Stage;
use Illuminate\Http\JsonResponse;

class StageController extends Controller
{
    public function index(): JsonResponse
    {
        $stages = Stage::orderBy('name')->get(['id', 'name', 'slug']);

        return response()->json($stages);
    }
}
