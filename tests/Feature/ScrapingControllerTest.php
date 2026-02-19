<?php



namespace Tests\Feature;

use App\Models\OpportunitySource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ScrapingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
    }

    public function test_admin_can_trigger_scrape(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(\Spatie\Permission\Models\Role::findByName('admin', 'api'));
        $token = auth('api')->login($admin);

        OpportunitySource::create([
            'name' => 'Test Source',
            'base_url' => 'https://example.com',
            'scraping_strategy' => 'default',
            'active' => true,
        ]);

        Http::fake(['*' => Http::response('<html><body>Content</body></html>', 200)]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/scraping/run');

        $response->assertStatus(200)
            ->assertJsonFragment(['message' => 'Scrape run completed']);
    }

    public function test_non_admin_cannot_trigger_scrape(): void
    {
        $user = User::factory()->create();
        $user->assignRole(\Spatie\Permission\Models\Role::findByName('startup', 'api'));
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/scraping/run');

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_trigger_scrape(): void
    {
        $response = $this->postJson('/api/scraping/run');
        $response->assertStatus(401);
    }
}
