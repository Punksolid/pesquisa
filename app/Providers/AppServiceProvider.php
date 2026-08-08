<?php

namespace App\Providers;

use App\Http\Middleware\AutoProvisionMcpClientUser;
use App\Models\Client;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::authorizationView('mcp::authorize');
        Passport::useClientModel(Client::class);

        Passport::tokensExpireIn(now()->addDays(15));
        Passport::refreshTokensExpireIn(now()->addDays(30));

        RateLimiter::for('mcp', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Runs once every provider (including Passport's) has booted and its
        // routes exist, so the named route is guaranteed to be registered.
        $this->app->booted(function (): void {
            Route::getRoutes()->getByName('passport.authorizations.authorize')
                ?->middleware(AutoProvisionMcpClientUser::class);
        });
    }
}
