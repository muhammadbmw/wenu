<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Auth;

class Foodie
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
        if (Auth::user()->role != 'foodie') {
            return response(json_encode(['error' => 'Unauthorised']), 401)
                ->header('Content-Type', 'text/json');
        }	
		return $next($request);
    }
}
