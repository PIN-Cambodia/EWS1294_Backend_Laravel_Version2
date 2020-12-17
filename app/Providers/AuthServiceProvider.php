<?php

namespace App\Providers;
use App\Permission;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

use App\Role;
use App\User;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        $permissionArray = [];


        // This check allows us to run composer installation etc before the db has been created
        if (Schema::hasTable(with(new Role())->getTable()) ) {
            $roles = Role::with('permission')->get();
            foreach ($roles as $role) {
                foreach ($role->permission as $permission) {
                    $permissionArray[$permission->key][] = $role->id;
                }
            }

           // foreach (Permission::with('roles')->get() as $key => $roles) {
            foreach ( $permissionArray as $key => $roles) {
                Gate::define($key, function (User $user) use ($roles) {
                    return count(array_intersect($user->role()->pluck('id')->toArray(), $roles));
                });
            }
        }

        Passport::routes();
        Passport::tokensExpireIn(now()->addYears(18));
        Passport::refreshTokensExpireIn(now()->addYears(18));
    }
}
