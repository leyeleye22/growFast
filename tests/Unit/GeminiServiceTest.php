<?php



namespace Tests\Unit;

use App\Services\GeminiService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiServiceTest extends TestCase
{
    public function test_is_configured_returns_false_when_no_api_key(): void
    {
        config(['services.gemini.api_key' => '']);
        $service = app(GeminiService::class);
        $this->assertFalse($service->isConfigured());
    }

    public function test_generate_content_returns_null_when_not_configured(): void
    {
        config(['services.gemini.api_key' => '']);
        $service = app(GeminiService::class);
        $this->assertNull($service->generateContent('test prompt'));
    }

    public function test_extract_opportunity_returns_null_when_not_configured(): void
    {
        config(['services.gemini.api_key' => '']);
        $service = app(GeminiService::class);
        $this->assertNull($service->extractOpportunityFromContent('<html>Grant content</html>'));
    }

    public function test_extract_opportunity_returns_array_when_configured(): void
    {
        config(['services.gemini.api_key' => 'test-key']);
        Http::fake([
            '*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => '{"title":"Test Grant","funding_type":"grant","deadline":"2026-12-31","industry":null,"stage":null,"funding_min":null,"funding_max":null}'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = app(GeminiService::class);
        $result = $service->extractOpportunityFromContent('<html>Grant content</html>');

        $this->assertIsArray($result);
        $this->assertEquals('Test Grant', $result['title']);
        $this->assertEquals('grant', $result['funding_type']);
    }
}
