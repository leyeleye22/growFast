<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Opportunity;
use App\Models\SavedOpportunity;
use App\Models\Startup;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Throwable;

class SavedOpportunityController extends Controller
{
    public function index(Startup $startup): JsonResponse
    {
        try {
            if ($startup->user_id !== request()->user()->id) {
                abort(403);
            }
            LogService::request('GET', 'SavedOpportunityController@index', ['startup_id' => $startup->id]);

            $saved = $startup->savedOpportunities()
                ->with(['opportunity' => fn ($q) => $q->withoutGlobalScopes()])
                ->orderByDesc('created_at')
                ->get()
                ->pluck('opportunity');

            return response()->json($saved);
        } catch (Throwable $e) {
            LogService::exception($e, 'SavedOpportunityController@index failed');
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

            LogService::request('POST', 'SavedOpportunityController@save', [
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
                'message' => 'Opportunité sauvegardée. Vous recevrez un rappel avant la date limite.',
                'saved_opportunity' => $saved->load('opportunity'),
            ], 201);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Opportunité introuvable'], 404);
        } catch (Throwable $e) {
            LogService::exception($e, 'SavedOpportunityController@save failed');
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

            LogService::request('DELETE', 'SavedOpportunityController@unsave', [
                'startup_id' => $startup->id,
                'opportunity_id' => $opportunityModel->id,
            ]);

            SavedOpportunity::where('startup_id', $startup->id)
                ->where('opportunity_id', $opportunityModel->id)
                ->delete();

            return response()->json(['message' => 'Opportunité retirée des sauvegardes']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Opportunité introuvable'], 404);
        } catch (Throwable $e) {
            LogService::exception($e, 'SavedOpportunityController@unsave failed');
            throw $e;
        }
    }
}
