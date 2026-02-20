<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Startup;
use App\services\OpportunityMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class MatchingController extends Controller
{
    public function __construct(
        private readonly OpportunityMatchingService $matchingService
    ) {}

    public function index(Startup $startup): JsonResponse
    {
        try {
            if ($startup->user_id !== request()->user()->id) {
                abort(403);
            }
            Log::info('[GET] MatchingController@index', ['startup_id' => $startup->id]);
            $matches = $this->matchingService->calculateMatches($startup);
            return response()->json($matches);
        } catch (Throwable $e) {
            Log::error('MatchingController@index failed', ['exception' => $e]);
            throw $e;
        }
    }
}
