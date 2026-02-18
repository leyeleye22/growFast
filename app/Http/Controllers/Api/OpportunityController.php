<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOpportunityRequest;
use App\Http\Requests\UpdateOpportunityRequest;
use App\Models\Opportunity;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Throwable;

class OpportunityController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            LogService::request('GET', 'OpportunityController@index');
            $opportunities = Opportunity::active()
                ->with(['subscriptionRequired', 'industries', 'stages'])
                ->paginate(15);
            LogService::info('Opportunities listed', ['count' => $opportunities->total()]);
            return response()->json($opportunities);
        } catch (Throwable $e) {
            LogService::exception($e, 'OpportunityController@index failed');
            throw $e;
        }
    }

    public function show(Opportunity $opportunity): JsonResponse
    {
        try {
            LogService::request('GET', 'OpportunityController@show', ['opportunity_id' => $opportunity->id]);
            $this->authorize('view', $opportunity);
            $opportunity->load(['subscriptionRequired', 'industries', 'stages', 'countryCodes']);
            return response()->json($opportunity);
        } catch (Throwable $e) {
            LogService::exception($e, 'OpportunityController@show failed');
            throw $e;
        }
    }

    public function store(StoreOpportunityRequest $request): JsonResponse
    {
        try {
            LogService::request('POST', 'OpportunityController@store');
            $opportunity = Opportunity::create($request->validated());
            LogService::info('Opportunity created', ['opportunity_id' => $opportunity->id, 'title' => $opportunity->title]);
            return response()->json($opportunity, 201);
        } catch (Throwable $e) {
            LogService::exception($e, 'OpportunityController@store failed');
            throw $e;
        }
    }

    public function update(UpdateOpportunityRequest $request, Opportunity $opportunity): JsonResponse
    {
        try {
            $this->authorize('update', $opportunity);
            LogService::request('PUT', 'OpportunityController@update', ['opportunity_id' => $opportunity->id]);
            $opportunity->update($request->validated());
            return response()->json($opportunity);
        } catch (Throwable $e) {
            LogService::exception($e, 'OpportunityController@update failed');
            throw $e;
        }
    }

    public function destroy(Opportunity $opportunity): JsonResponse
    {
        try {
            $this->authorize('delete', $opportunity);
            LogService::request('DELETE', 'OpportunityController@destroy', ['opportunity_id' => $opportunity->id]);
            $opportunity->delete();
            return response()->json(null, 204);
        } catch (Throwable $e) {
            LogService::exception($e, 'OpportunityController@destroy failed');
            throw $e;
        }
    }
}
