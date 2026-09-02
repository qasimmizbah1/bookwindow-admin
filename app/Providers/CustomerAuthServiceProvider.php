<?php

namespace App\Providers;

use App\Models\Customer;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;



class CustomerAuthServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->registerPolicies();

        // // Custom password broker for customers
        // Password::extend('customers', function () {
        //     return new \Illuminate\Auth\Passwords\PasswordBroker(
        //         $this->app['auth']->createUserProvider('customers'),
        //         $this->app['mailer'],
        //         $this->app['config']['auth.passwords.customers'],
        //         $this->app['hash']
        //     );
        // });

        // Custom reset password link
        ResetPassword::createUrlUsing(function (Customer $customer, string $token) {
            return config('app.frontend_url').'/reset-password?token='.$token.'&email='.$customer->email;
        });
        
    }
}