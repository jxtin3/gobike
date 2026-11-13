<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends visitors (and Google) from the other addresses that reach this site, such as
 * www.gobikeproject.org and the old Railway address, to the one public address, keeping the
 * path and query. Otherwise Google sees several copies of the site and splits their ranking.
 *
 * Only the hosts in config('app.redirect_hosts') are redirected, so an unexpected host (local,
 * tests, a proxy) is left alone and a redirect loop cannot happen.
 */
class RedirectToCanonicalHost
{
    public function handle(Request $request, Closure $next): Response
    {
        $hosts = array_map('strtolower', (array) config('app.redirect_hosts', []));

        if (! in_array(strtolower($request->getHost()), $hosts, true) || $request->is('up')) {
            return $next($request);
        }

        $target = rtrim((string) config('app.canonical_url'), '/').$request->getRequestUri();

        // 301 for pages. 308 keeps the method and body, so an old app build still posting to the
        // old address reaches the live API.
        $status = in_array($request->getMethod(), ['GET', 'HEAD'], true) ? 301 : 308;

        return redirect()->away($target, $status);
    }
}
