<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Startup;
use App\Services\LogService;
use App\Services\OpportunityMatchingService;
use Illuminate\Http\JsonResponse;
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
            LogService::request('GET', 'MatchingController@index', ['startup_id' => $startup->id]);
            $matches = $this->matchingService->calculateMatches($startup);
            return response()->json($matches);
        } catch (Throwable $e) {
            LogService::exception($e, 'MatchingController@index failed');
            throw $e;
        }
    }
}
