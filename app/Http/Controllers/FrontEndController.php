<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class FrontEndController extends Controller
{
    /**
     * Generic renderer for frontend pages.
     */
    private function render(string $page, Request $request)
    {
        /** @var \App\Models\Vendor $vendor */
        $vendor = app('domain');

        return Inertia::render("FrontEnd/{$page}/{$vendor->slug}", [
            'host'     => $vendor->domain,
            'slug'     => $vendor->slug,
            'name'     => $vendor->name,
            'theme'    => [
                'primary'   => $vendor->primary_color ?? 'hsl(0 0% 9%)',
                'secondary' => $vendor->secondary_color ?? 'hsl(0 0% 92.1%)',
                'accent'    => $vendor->accent_color ?? 'hsl(0 0% 96.1%)',
            ],
            'units' => $vendor->units,
        ]);
    }

    public function index(Request $request)
    {
        return $this->render('Index', $request);
    }

    public function about(Request $request)
    {
        return $this->render('About', $request);
    }

    public function contact(Request $request)
    {
        return $this->render('Contact', $request);
    }

    public function privacyPolicy(Request $request)
    {
        return $this->render('PrivacyPolicy', $request);
    }

    public function cancellationPolicy(Request $request)
    {
        return $this->render('CancellationPolicy', $request);
    }

    public function nearbyAttractions(Request $request)
    {
        return $this->render('NearbyAttractions', $request);
    }

    public function accommodations(Request $request)
    {
        return $this->render('Accommodations', $request);
    }

    public function villas(Request $request)
    {
        return $this->render('Villas', $request);
    }

    public function spa(Request $request)
    {
        return $this->render('Spa', $request);
    }
}
