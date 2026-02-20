<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOpportunityRequest;
use App\Http\Requests\UpdateOpportunityRequest;
use App\Models\Opportunity;
use App\services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class OpportunityController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            Log::info('[GET] OpportunityController@index');
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

    /**
     * Ask Gemini a question about an opportunity.
     * POST body: { "question": "What is the deadline?" }
     */
    public function ask(Request $request, Opportunity $opportunity): JsonResponse
    {
        $question = trim((string) $request->input('question', ''));
        if ($question === '') {
            return response()->json(['message' => 'Question is required.'], 400);
        }

        $gemini = app(GeminiService::class);
        $answer = $gemini->askAboutOpportunity($opportunity, $question);

        if ($answer === null) {
            return response()->json([
                'message' => 'Gemini is not configured or could not generate an answer.',
                'answer' => null,
            ], 503);
        }

        return response()->json([
            'answer' => $answer,
            'question' => $question,
        ]);
    }
}
