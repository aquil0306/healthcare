<?php

namespace App\Providers;

use App\Events\ReferralTriaged;
use App\Listeners\SendReferralNotifications;
use App\Models\Hospital;
use App\Models\Patient;
use App\Models\Referral;
use App\Models\Staff;
use App\Models\User;
use App\Observers\StaffObserver;
use App\Repositories\HospitalRepository;
use App\Repositories\PatientRepository;
use App\Repositories\ReferralRepository;
use App\Repositories\StaffRepository;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register repositories
        $this->app->singleton(HospitalRepository::class, function ($app) {
            return new HospitalRepository(new Hospital);
        });

        $this->app->singleton(PatientRepository::class, function ($app) {
            return new PatientRepository(new Patient);
        });

        $this->app->singleton(ReferralRepository::class, function ($app) {
            return new ReferralRepository(new Referral);
        });

        $this->app->singleton(StaffRepository::class, function ($app) {
            return new StaffRepository(new Staff);
        });
    }

    public function boot(): void
    {
        // Configure morph map for polymorphic relationships (required for Spatie Permission)
        // Maps the stored class name in the database to the actual model class
        // This ensures Spatie Permission can properly resolve the User model
        // The key must match EXACTLY what's stored in the database (App\Models\User)
        Relation::morphMap([
            'App\Models\User' => User::class,
        ], false);

        // Register event listeners
        Event::listen(ReferralTriaged::class, SendReferralNotifications::class);

        // Register observers
        Staff::observe(StaffObserver::class);
    }
}
