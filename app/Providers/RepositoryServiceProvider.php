<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\TallyCompanyRepositoryInterface;
use App\Repositories\Eloquent\TallyCompanyRepository;
// Other repository bindings...

class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(TallyCompanyRepositoryInterface::class, TallyCompanyRepository::class);
        // Bind other repositories...
    }

    public function boot()
    {
        //
    }
}
