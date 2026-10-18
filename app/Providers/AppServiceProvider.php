<?php

namespace App\Providers;

use App\Models\AuditLog;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        Password::defaults(fn () => Password::min(12)->letters()->numbers());
        Paginator::useBootstrapFive();
        RateLimiter::for('vehicle-writes', function ($request) {
            return Limit::perHour(30)->by((string) $request->user()->id)
                ->response(function ($request, $headers) {
                    AuditLog::create(['user_id' => $request->user()->id, 'action' => 'suspicious_write_rate',
                        'table_name' => 'vehicles', 'ip_address' => $request->ip()]);

                    return response('Límite de operaciones alcanzado. Intenta más tarde.', 429, $headers);
                });
        });
    }
}
