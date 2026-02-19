<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStartupRequest;
use App\Http\Requests\UpdateStartupRequest;
use App\Models\Startup;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Throwable;

class StartupController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            Log::info('[GET] StartupController@index');
            $startups = request()->user()->startups()->paginate(15);
            Log::info('Startups listed', ['count' => $startups->total()]);
            return response()->json($startups);
        } catch (Throwable $e) {
            Log::error('StartupController@index failed', ['exception' => $e]);
            throw $e;
        }
    }

    public function store(StoreStartupRequest $request): JsonResponse
    {
        try {
            Log::info('[POST] StartupController@store');
            $startup = request()->user()->startups()->create($request->validated());
            Log::info('Startup created', ['startup_id' => $startup->id]);
            return response()->json($startup, 201);
        } catch (Throwable $e) {
            Log::error('StartupController@store failed', ['exception' => $e]);
            throw $e;
        }
    }

    public function show(Startup $startup): JsonResponse
    {
        try {
            $this->authorize('view', $startup);
            Log::info('[GET] StartupController@show', ['startup_id' => $startup->id]);
            $startup->load('documents');
            return response()->json($startup);
        } catch (Throwable $e) {
            Log::error('StartupController@show failed', ['exception' => $e]);
            throw $e;
        }
    }

    public function update(UpdateStartupRequest $request, Startup $startup): JsonResponse
    {
        try {
            $this->authorize('update', $startup);
            Log::info('[PUT] StartupController@update', ['startup_id' => $startup->id]);
            $startup->update($request->validated());
            return response()->json($startup);
        } catch (Throwable $e) {
            Log::error('StartupController@update failed', ['exception' => $e]);
            throw $e;
        }
    }

    public function destroy(Startup $startup): JsonResponse
    {
        try {
            $this->authorize('delete', $startup);
            Log::info('[DELETE] StartupController@destroy', ['startup_id' => $startup->id]);
            $startup->delete();
            return response()->json(null, 204);
        } catch (Throwable $e) {
            Log::error('StartupController@destroy failed', ['exception' => $e]);
            throw $e;
        }
    }
}
