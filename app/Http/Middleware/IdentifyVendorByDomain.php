<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use App\Models\Vendor;

class IdentifyVendorByDomain
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next)
    {
        $host = $request->getHost(); // e.g. lakebaturcabin.com

        // Log::info('Incoming request from domain: ' . $host);

        $vendor = Vendor::where('domain', $host)->first();

        if (! $vendor) {
            abort(404, 'Vendor not found');
        }

        // Share vendor globally (accessible via request() or view())
        app()->instance('currentVendor', $vendor);

        return $next($request);
    }
}
