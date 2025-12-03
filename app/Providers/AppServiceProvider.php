<?php

namespace App\Providers;

use App\Http\Responses\LoginResponse;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share standardized alert to Inertia pages
        Inertia::share('alert', function () {
            // Map common session keys to a unified alert payload
            $keys = ['success', 'error', 'warning', 'info', 'status'];
            foreach ($keys as $key) {
                if (session()->has($key)) {
                    $message = session()->get($key);
                    $type = $key === 'status' ? 'info' : $key;
                    return [
                        'type' => $type,
                        'message' => is_string($message) ? $message : (string) $message,
                    ];
                }
            }

            // Also support a pre-structured 'alert' array flashed from controllers
            if (session()->has('alert')) {
                $alert = session()->get('alert');
                if (is_array($alert) && isset($alert['type'], $alert['message'])) {
                    return [
                        'type' => $alert['type'],
                        'message' => $alert['message'],
                        'description' => $alert['description'] ?? null,
                    ];
                }
            }

            return null;
        });
    }
}
