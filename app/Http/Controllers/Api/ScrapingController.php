<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Artisan;
use Throwable;

class ScrapingController extends Controller
{
    public function run(): JsonResponse
    {
        try {
            if (!request()->user()->can('run_scraper')) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
            LogService::request('POST', 'ScrapingController@run');
            Artisan::call('scrape:run', ['--triggered-by' => 'api']);
            LogService::info('Scrape run triggered');
            return response()->json(['message' => 'Scrape run completed']);
        } catch (Throwable $e) {
            LogService::exception($e, 'ScrapingController@run failed');
            throw $e;
        }
    }
}
