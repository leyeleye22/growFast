<?php



namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOpportunitySuggestionRequest;
use App\Models\OpportunitySuggestion;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Throwable;

class OpportunitySuggestionController extends Controller
{
    public function store(StoreOpportunitySuggestionRequest $request): JsonResponse
    {
        try {
            LogService::request('POST', 'OpportunitySuggestionController@store');
            $data = $request->validated();
            $data['user_id'] = $request->user()?->id;
            $suggestion = OpportunitySuggestion::create($data);
            LogService::info('Opportunity suggested', ['suggestion_id' => $suggestion->id]);
            return response()->json($suggestion, 201);
        } catch (Throwable $e) {
            LogService::exception($e, 'OpportunitySuggestionController@store failed');
            throw $e;
        }
    }
}
