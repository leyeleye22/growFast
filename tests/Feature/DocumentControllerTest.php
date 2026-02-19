<?php



namespace Tests\Feature;

use App\Models\Document;
use App\Models\Startup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        Storage::fake('local');
    }

    public function test_authenticated_user_can_list_documents(): void
    {
        $user = User::factory()->create();
        $startup = Startup::create(['user_id' => $user->id, 'name' => 'My Startup']);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/startups/' . $startup->id . '/documents');

        $response->assertStatus(200);
        $this->assertIsArray($response->json());
    }

    public function test_authenticated_user_can_upload_document(): void
    {
        $user = User::factory()->create();
        $startup = Startup::create(['user_id' => $user->id, 'name' => 'My Startup']);
        $token = auth('api')->login($user);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->post('/api/startups/' . $startup->id . '/documents', [
                'file' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'document.pdf']);
        $this->assertDatabaseHas('documents', ['name' => 'document.pdf']);
    }

    public function test_authenticated_user_cannot_upload_to_other_startup(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $startup = Startup::create(['user_id' => $other->id, 'name' => 'Other Startup']);
        $token = auth('api')->login($user);

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->post('/api/startups/' . $startup->id . '/documents', [
                'file' => $file,
            ]);

        $response->assertStatus(403);
    }

    public function test_authenticated_user_can_delete_own_document(): void
    {
        $user = User::factory()->create();
        $startup = Startup::create(['user_id' => $user->id, 'name' => 'My Startup']);
        $document = Document::create([
            'startup_id' => $startup->id,
            'name' => 'doc.pdf',
            'path' => 'documents/test.pdf',
        ]);
        $token = auth('api')->login($user);

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->deleteJson('/api/startups/' . $startup->id . '/documents/' . $document->id);

        $response->assertStatus(204);
        $this->assertSoftDeleted('documents', ['id' => $document->id]);
    }

    public function test_unauthenticated_user_cannot_access_documents(): void
    {
        $user = User::factory()->create();
        $startup = Startup::create(['user_id' => $user->id, 'name' => 'Startup']);

        $response = $this->getJson('/api/startups/' . $startup->id . '/documents');
        $response->assertStatus(401);
    }
}
