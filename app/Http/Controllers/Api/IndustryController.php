<?php



namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use Illuminate\Http\JsonResponse;

class IndustryController extends Controller
{
    public function index(): JsonResponse
    {
        $industries = Industry::orderBy('name')->get(['id', 'name', 'slug']);

        return response()->json($industries);
    }
}
