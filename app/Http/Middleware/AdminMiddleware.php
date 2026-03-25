<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
       public function handle(Request $request, Closure $next)
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json(['error'=>'Unauthorized'], 401);
        }


        return $next($request);
    }
}
