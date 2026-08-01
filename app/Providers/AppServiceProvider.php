<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
        $this->configureDefaults();
        $this->configureGates();
        $this->recordAuthActivity();
    }

    /** Lets both staff (view-trips) and drivers (view-own-trips) reach the trips board. */
    protected function configureGates(): void
    {
        Gate::define('access-trips', fn (User $user) => $user->canAny(['view-trips', 'view-own-trips']));
    }

    /** Records login/logout events to the activity log. */
    protected function recordAuthActivity(): void
    {
        Event::listen(Login::class, fn (Login $event) => ActivityLog::create([
            'user_id' => $event->user->getAuthIdentifier(),
            'event' => 'login',
            'description' => 'Logged in',
        ]));

        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user) {
                ActivityLog::create([
                    'user_id' => $event->user->getAuthIdentifier(),
                    'event' => 'logout',
                    'description' => 'Logged out',
                ]);
            }
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Render every full-page Livewire screen inside the app sidebar shell
        // (resources/views/layouts/app.blade.php) instead of Livewire's default.
        config(['livewire.layout' => 'layouts.app']);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
