<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOpportunitySuggestionRequest;
use App\Models\OpportunitySuggestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpportunitySuggestionController extends Controller
{
    public function store(StoreOpportunitySuggestionRequest $request): JsonResponse
    {
        try {
            Log::info('[POST] OpportunitySuggestionController@store');
            $data = $request->validated();
            $data['user_id'] = $request->user()?->id;
            $suggestion = OpportunitySuggestion::create($data);
            Log::info('Opportunity suggested', ['suggestion_id' => $suggestion->id]);
            return response()->json($suggestion, 201);
        } catch (Throwable $e) {
            Log::error('OpportunitySuggestionController@store failed', ['exception' => $e]);
            throw $e;
        }
    }
}
