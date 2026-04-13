<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$guards): Response
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();
                if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
                    return redirect()->route('admin.dashboard');
                }
                if ($user && method_exists($user, 'isCompany') && $user->isCompany()) {
                    return redirect()->route('company.jobs.index');
                }

                return redirect()->route('dashboard');
            }
        }

        return $next($request);
    }
}
