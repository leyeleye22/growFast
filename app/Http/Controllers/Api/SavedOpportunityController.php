<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Models\SavedOpportunity;
use App\Models\Startup;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class SavedOpportunityController extends Controller
{
    public function index(Startup $startup): JsonResponse
    {
        try {
            if ($startup->user_id !== request()->user()->id) {
                abort(403);
            }
            Log::info('[GET] SavedOpportunityController@index', ['startup_id' => $startup->id]);

            $saved = $startup->savedOpportunities()
                ->with(['opportunity' => fn ($q) => $q->withoutGlobalScopes()])
                ->orderByDesc('created_at')
                ->get()
                ->pluck('opportunity');

            return response()->json($saved);
        } catch (Throwable $e) {
            Log::error('SavedOpportunityController@index failed', ['exception' => $e]);
            throw $e;
        }
    }

    public function save(Startup $startup, string $opportunity): JsonResponse
    {
        try {
            if ($startup->user_id !== request()->user()->id) {
                abort(403);
            }
            $opportunityModel = Opportunity::withoutGlobalScopes()->findOrFail($opportunity);

            Log::info('[POST] SavedOpportunityController@save', [
                'startup_id' => $startup->id,
                'opportunity_id' => $opportunityModel->id,
            ]);

            $saved = SavedOpportunity::firstOrCreate(
                [
                    'startup_id' => $startup->id,
                    'opportunity_id' => $opportunityModel->id,
                ],
                []
            );

            return response()->json([
                'message' => 'Opportunity saved. You will receive a reminder before the deadline.',
                'saved_opportunity' => $saved->load('opportunity'),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Opportunity not found'], 404);
        } catch (Throwable $e) {
            Log::error('SavedOpportunityController@save failed', ['exception' => $e]);
            throw $e;
        }
    }

    public function unsave(Startup $startup, string $opportunity): JsonResponse
    {
        try {
            if ($startup->user_id !== request()->user()->id) {
                abort(403);
            }
            $opportunityModel = Opportunity::withoutGlobalScopes()->findOrFail($opportunity);

            Log::info('[DELETE] SavedOpportunityController@unsave', [
                'startup_id' => $startup->id,
                'opportunity_id' => $opportunityModel->id,
            ]);

            SavedOpportunity::where('startup_id', $startup->id)
                ->where('opportunity_id', $opportunityModel->id)
                ->delete();

            return response()->json(['message' => 'Opportunity removed from saved']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Opportunity not found'], 404);
        } catch (Throwable $e) {
            Log::error('SavedOpportunityController@unsave failed', ['exception' => $e]);
            throw $e;
        }
    }
}
