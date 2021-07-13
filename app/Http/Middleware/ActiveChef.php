<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;

class ActiveChef
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
       if (Auth::user()->role == 'chef' && Auth::user()->chef_status == 1) {
			return $next($request);
		 }
		 else {
			    return response(json_encode(['error' => 'Complete your profile']), 405)
                ->header('Content-Type', 'text/json');
		 }
    }
}
