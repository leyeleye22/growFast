<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Throwable;

class ScrapingController extends Controller
{
    public function run(): JsonResponse
    {
        try {
            if (!request()->user()->can('run_scraper')) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
            Log::info('[POST] ScrapingController@run');
            Artisan::call('scrape:run', ['--triggered-by' => 'api']);
            Log::info('Scrape run triggered');
            return response()->json(['message' => 'Scrape run completed']);
        } catch (Throwable $e) {
            Log::error('ScrapingController@run failed', ['exception' => $e]);
            throw $e;
        }
    }
}
