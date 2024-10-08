<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckUserRoleAndStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if user is authenticated
        if (auth()->check()) {
            $user = auth()->user();

            // Check user status and role
            if ($user->status == 1 && ($user->role == 'Owner' || $user->role == 'Employee')) {
                
                return $next($request);
            }
        }

        // If not allowed, redirect or show error
        return redirect('/')->with('error', 'Access denied!');
    }
}
