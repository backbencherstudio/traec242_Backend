<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }


    public function boot(): void
    {
        // 1. Super Admin Gate Bypass
        // This allows Super Admins to bypass all permission checks
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });


        try {
            if (Schema::hasTable('settings')) {
                $settings = DB::table('settings')->first();

                if ($settings) {
                    config([
                        'broadcasting.connections.pusher.id' => $settings->pusher_app_id,
                        'broadcasting.connections.pusher.key' => $settings->pusher_app_key,
                        'broadcasting.connections.pusher.secret' => $settings->pusher_app_secret,
                        'broadcasting.connections.pusher.options.cluster' => $settings->pusher_app_cluster,

                        'services.google.client_id' => $settings->google_client_id,
                        'services.google.client_secret' => $settings->google_client_secret,
                        'services.google.redirect' => $settings->google_redirect_url,
                        'services.google.scopes' => ['openid', 'profile', 'email'],
                    ]);
                }
            }
        } catch (\Exception $e) {

        }
    }
}
