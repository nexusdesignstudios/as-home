<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize as BaseValidatePostSize;

class CustomValidatePostSize extends BaseValidatePostSize
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, $next)
    {
        // Check if post size validation should be skipped
        if ($request->attributes->get('skip_post_size_validation', false)) {
            return $next($request);
        }

        // Check if this is one of the specific API endpoints that should skip validation
        if (
            $request->is('api/*') &&
            ($request->path() === 'api/post_property' ||
                $request->path() === 'api/update_post_property' ||
                str_contains($request->path(), 'post_property') ||
                str_contains($request->path(), 'update_post_property'))
        ) {
            return $next($request);
        }

        // Apply the original ValidatePostSize logic
        return parent::handle($request, $next);
    }
}
