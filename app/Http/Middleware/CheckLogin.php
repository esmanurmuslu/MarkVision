<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckLogin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!session()->has('teacher_id')) {
            return redirect()->route('obs.login');
        }

        return $next($request);
    }
}