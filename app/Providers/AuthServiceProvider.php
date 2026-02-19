<?php



namespace App\Providers;

use App\Models\Document;
use App\Models\Opportunity;
use App\Models\Startup;
use App\Policies\DocumentPolicy;
use App\Policies\OpportunityPolicy;
use App\Policies\StartupPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Opportunity::class => OpportunityPolicy::class,
        Document::class => DocumentPolicy::class,
        Startup::class => StartupPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
