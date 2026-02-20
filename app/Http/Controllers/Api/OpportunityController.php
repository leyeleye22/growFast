<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOpportunityRequest;
use App\Http\Requests\UpdateOpportunityRequest;
use App\Models\Opportunity;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpportunityController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            Log::info('[GET] OpportunityController@index');
            dd([
    'all' => Opportunity::withoutGlobalScopes()->count(),
    'not_expired_scope' => Opportunity::count(),
    'active' => Opportunity::withoutGlobalScopes()->active()->count(),
]);
            $opportunities = Opportunity::active()
                ->with(['industries', 'stages'])
                ->get();
            Log::info('Opportunities listed', ['count' => $opportunities->count()]);
            return response()->json($opportunities);
        } catch (Throwable $e) {
            Log::error('OpportunityController@index failed', ['exception' => $e]);
            throw $e;
        }
    }

    public function show(Opportunity $opportunity): JsonResponse
    {
        try {
            Log::info('[GET] OpportunityController@show', ['opportunity_id' => $opportunity->id]);
            $opportunity->load([ 'industries', 'stages', 'countryCodes']);
            return response()->json($opportunity);
        } catch (Throwable $e) {
            Log::error('OpportunityController@show failed', ['exception' => $e]);
            throw $e;
        }
    }

    public function store(StoreOpportunityRequest $request): JsonResponse
    {
        try {
            Log::info('[POST] OpportunityController@store');
            $opportunity = Opportunity::create($request->validated());
            Log::info('Opportunity created', ['opportunity_id' => $opportunity->id, 'title' => $opportunity->title]);
            return response()->json($opportunity, 201);
        } catch (Throwable $e) {
            Log::error('OpportunityController@store failed', ['exception' => $e]);
            throw $e;
        }
    }

    public function update(UpdateOpportunityRequest $request, Opportunity $opportunity): JsonResponse
    {
        try {
            $this->authorize('update', $opportunity);
            Log::info('[PUT] OpportunityController@update', ['opportunity_id' => $opportunity->id]);
            $opportunity->update($request->validated());
            return response()->json($opportunity);
        } catch (Throwable $e) {
            Log::error('OpportunityController@update failed', ['exception' => $e]);
            throw $e;
        }
    }

    public function destroy(Opportunity $opportunity): JsonResponse
    {
        try {
            $this->authorize('delete', $opportunity);
            Log::info('[DELETE] OpportunityController@destroy', ['opportunity_id' => $opportunity->id]);
            $opportunity->delete();
            return response()->json(null, 204);
        } catch (Throwable $e) {
            Log::error('OpportunityController@destroy failed', ['exception' => $e]);
            throw $e;
        }
    }
}
