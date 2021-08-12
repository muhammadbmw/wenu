<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;

class FoodieOrGuest
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
         if (Auth::user()->role == 'foodie' || Auth::user()->role == 'guest') {
			return $next($request);
		 }
		 else {
			     return response(json_encode(['error' => 'Unauthorised']), 405)
                ->header('Content-Type', 'text/json');
		 }
    }
}
