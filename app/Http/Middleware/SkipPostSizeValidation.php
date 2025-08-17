<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SkipPostSizeValidation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip post size validation for specific API endpoints
        if (
            $request->is('api/*') &&
            ($request->route()->getName() === 'post_property' ||
                $request->route()->getName() === 'update_post_property' ||
                $request->path() === 'api/post_property' ||
                $request->path() === 'api/update_post_property')
        ) {

            // Remove the ValidatePostSize middleware from the request
            $request->attributes->set('skip_post_size_validation', true);
        }

        return $next($request);
    }
}
