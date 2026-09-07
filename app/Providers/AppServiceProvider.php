<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Ensure CMS helper functions are always available, even if the
        // Composer autoload files list hasn't been reloaded by a long-running server.
        require_once app_path('helpers.php');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Laravel's bundled pagination views are Tailwind markup, and the
        // public site loads no Tailwind — the utility classes go inert and the
        // chevron SVGs render at full page width. Ours is plain markup styled
        // by the .pagination block in public/css/app.css. This only affects
        // ->links() calls: the admin's Livewire tables name their own view.
        Paginator::defaultView('vendor.pagination.applyd');
        Paginator::defaultSimpleView('vendor.pagination.applyd-simple');
    }
}
