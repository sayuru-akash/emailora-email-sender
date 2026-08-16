<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventSearchIndexing
{
    public const ROBOTS_DIRECTIVE = 'noindex, nofollow, noarchive';

    /**
     * Add a crawler directive to every non-public web response, including downloads.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! in_array($request->route()?->getName(), ['home', 'privacy', 'terms'], true)) {
            $response->headers->set('X-Robots-Tag', self::ROBOTS_DIRECTIVE);
        }

        return $response;
    }
}
