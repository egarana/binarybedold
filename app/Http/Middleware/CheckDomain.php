<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Models\Vendor;

class CheckDomain
{
    public function handle(Request $request, Closure $next, string $rule)
    {
        $host = $request->getHost();

        if ($rule === 'admin') {
            if (!preg_match('/^admin\.(?:[a-z0-9-]+\.)+com$/i', $host)) {
                abort(404, 'Unauthorized access (admin domain required).');
            }

            $parsed = $this->parseDomain($host, true);

            app()->instance('domain', $parsed);
            view()->share('domain', $parsed);

            if ($parsed?->sld) {
                URL::defaults(['base' => $parsed->sld]);
            }

            return $next($request);
        }

        if ($rule === 'frontend') {
            if (preg_match('/^admin\.(?:[a-z0-9-]+\.)+com$/i', $host)) {
                abort(404, 'Unauthorized access (frontend domain required).');
            }

            if (!preg_match('/^(?:[a-z0-9-]+\.)+com$/i', $host)) {
                abort(404, 'Unauthorized access (frontend domain required).');
            }

            $parsed = $this->parseDomain($host);

            // 🔹 Resolve Vendor from DB
            $vendor = Vendor::where('slug', $parsed->slug)
                ->orWhere('domain', $parsed->host)
                ->first();

            if (! $vendor) {
                abort(404, 'Vendor not found.');
            }

            app()->instance('domain', $vendor);
            view()->share('domain', $vendor);

            return $next($request);
        }

        return $next($request);
    }

    // private function parseDomain(string $host): object
    // {
    //     $parts = explode('.', $host);

    //     return (object)[
    //         'host' => $host,
    //         'parts' => $parts,
    //         'tld' => $parts[count($parts) - 1] ?? null,
    //         'sld' => $parts[count($parts) - 2] ?? null,
    //         'sub' => implode('.', array_slice($parts, 0, -2)),
    //         'slug' => $parts[0] ?? null,
    //     ];
    // }

    private function parseDomain(string $host, bool $isAdmin = false): object
    {
        $parts = explode('.', $host);

        return (object)[
            'host' => $host,
            'parts' => $parts,
            'tld' => $parts[count($parts) - 1] ?? null,
            'sld' => $parts[count($parts) - 2] ?? null,
            'sub' => implode('.', array_slice($parts, 0, -2)),
            'slug' => $isAdmin
                ? ($parts[count($parts) - 2] ?? null) // ambil SLD kalau admin
                : ($parts[0] ?? null),               // ambil subdomain kalau frontend
        ];
    }
}
