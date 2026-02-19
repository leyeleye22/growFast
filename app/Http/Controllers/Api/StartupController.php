<?php



namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStartupRequest;
use App\Http\Requests\UpdateStartupRequest;
use App\Models\Startup;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Throwable;

class StartupController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            LogService::request('GET', 'StartupController@index');
            $startups = request()->user()->startups()->paginate(15);
            LogService::info('Startups listed', ['count' => $startups->total()]);
            return response()->json($startups);
        } catch (Throwable $e) {
            LogService::exception($e, 'StartupController@index failed');
            throw $e;
        }
    }

    public function store(StoreStartupRequest $request): JsonResponse
    {
        try {
            LogService::request('POST', 'StartupController@store');
            $startup = request()->user()->startups()->create($request->validated());
            LogService::info('Startup created', ['startup_id' => $startup->id]);
            return response()->json($startup, 201);
        } catch (Throwable $e) {
            LogService::exception($e, 'StartupController@store failed');
            throw $e;
        }
    }

    public function show(Startup $startup): JsonResponse
    {
        try {
            $this->authorize('view', $startup);
            LogService::request('GET', 'StartupController@show', ['startup_id' => $startup->id]);
            $startup->load('documents');
            return response()->json($startup);
        } catch (Throwable $e) {
            LogService::exception($e, 'StartupController@show failed');
            throw $e;
        }
    }

    public function update(UpdateStartupRequest $request, Startup $startup): JsonResponse
    {
        try {
            $this->authorize('update', $startup);
            LogService::request('PUT', 'StartupController@update', ['startup_id' => $startup->id]);
            $startup->update($request->validated());
            return response()->json($startup);
        } catch (Throwable $e) {
            LogService::exception($e, 'StartupController@update failed');
            throw $e;
        }
    }

    public function destroy(Startup $startup): JsonResponse
    {
        try {
            $this->authorize('delete', $startup);
            LogService::request('DELETE', 'StartupController@destroy', ['startup_id' => $startup->id]);
            $startup->delete();
            return response()->json(null, 204);
        } catch (Throwable $e) {
            LogService::exception($e, 'StartupController@destroy failed');
            throw $e;
        }
    }

}
