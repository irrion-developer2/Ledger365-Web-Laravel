<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
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
         Fortify::createUsersUsing(CreateNewUser::class);
         Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
         Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
         Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
 
         // Custom phone number authentication
         Fortify::authenticateUsing(function (Request $request) {
             $user = \App\Models\User::where('phone', $request->phone)->first();
 
             if ($user && $user->otp === $request->otp && now()->lessThanOrEqualTo($user->otp_expires_at)) {
                 return $user;
             }
 
             return null;
         });
 
         RateLimiter::for('login', function (Request $request) {
             $phone = (string) $request->phone;
 
             return Limit::perMinute(5)->by($phone.$request->ip());
         });
     }
     
     


    // public function boot(): void
    // {
    //     Fortify::createUsersUsing(CreateNewUser::class);
    //     Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
    //     Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
    //     Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

    //     RateLimiter::for('login', function (Request $request) {
    //         $email = (string) $request->email;

    //         return Limit::perMinute(5)->by($email.$request->ip());
    //     });

    //     RateLimiter::for('two-factor', function (Request $request) {
    //         return Limit::perMinute(5)->by($request->session()->get('login.id'));
    //     });
    // }
}
