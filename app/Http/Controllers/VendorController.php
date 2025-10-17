<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVendorRequest;
use App\Models\Vendor;
use App\Models\User;
use Inertia\Inertia;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Illuminate\Http\Request;
use Inertia\Response;
use Illuminate\Support\Arr;
use Illuminate\Http\RedirectResponse;

class VendorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        // Normalize 'search' to 'filter[search]'
        if ($request->filled('search')) {
            $request->merge([
                'filter' => ['search' => $request->input('search')]
            ]);
        }

        // Validate perPage
        $perPage = intval($request->query('perPage', 20));
        if (!in_array($perPage, [10, 20, 30, 40, 50])) {
            $perPage = 20;
        }

        // Define allowed sorts
        $allowedSorts = [
            'name',
            'domain',
            AllowedSort::field('units_count'),
            AllowedSort::field('users_count'),
            'created_at',
            'updated_at',
        ];

        // Extract sort keys for validation
        $allowedSortKeys = [
            'name',
            'domain',
            'units_count',
            'users_count',
            'created_at',
            'updated_at',
        ];

        // Validate and sanitize sort parameter
        $sort = $request->query('sort');
        if ($sort && !in_array(ltrim($sort, '-'), $allowedSortKeys)) {
            // Remove invalid sort from query
            $request->query->remove('sort');
            $sort = null;
        }

        // Build query
        $vendors = QueryBuilder::for(Vendor::class, $request)
            ->allowedFilters([
                AllowedFilter::callback('search', function ($query, $value) {
                    $query->where(function ($query) use ($value) {
                        $query->where('name', 'LIKE', "%{$value}%")
                            ->orWhere('domain', 'LIKE', "%{$value}%");
                    });
                }),
            ])
            ->allowedSorts($allowedSorts)
            ->withCount(['units', 'users'])
            ->with('users')
            ->paginate($perPage)
            ->withQueryString();

        return Inertia::render('Vendors/Index', [
            'vendors' => $vendors,
            'perPage' => $perPage,
            'search' => $request->input('search'),
            'sort' => $sort,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        // Normalize 'search' to 'filter[search]'
        if ($request->filled('search')) {
            $request->merge([
                'filter' => ['search' => $request->input('search')]
            ]);
        }

        $users = QueryBuilder::for(User::class)
            ->allowedFilters([
                AllowedFilter::partial('search', 'name'),
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(function ($user) {
                return [
                    'value' => (string) $user->id,
                    'label' => [
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                ];
            });

        return Inertia::render('Vendors/Create', [
            'users' => $users,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVendorRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $vendor = Vendor::create(Arr::only($validated, ['name', 'domain', 'slug']));

        if (!empty($validated['users'])) {
            $userIds = collect($validated['users'])->pluck('value')->toArray();
            $vendor->users()->sync($userIds);
        }

        return redirect()->route('vendors.index');
    }


    /**
     * Display the specified resource.
     */
    public function show(Vendor $vendor)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Vendor $vendor, Request $request)
    {
        // Normalize 'search' to 'filter[search]'
        if ($request->filled('search')) {
            $request->merge([
                'filter' => ['search' => $request->input('search')]
            ]);
        }

        $users = QueryBuilder::for(User::class)
            ->allowedFilters([
                AllowedFilter::partial('search', 'name'),
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(function ($user) {
                return [
                    'value' => (string) $user->id,
                    'label' => [
                        'name' => $user->name,
                        'email' => $user->email,
                    ],
                ];
            });

        // Load and format selected users only
        $vendor->load('users');
        $formattedVendor = [
            'id' => $vendor->id,
            'name' => $vendor->name,
            'domain' => $vendor->domain,
            'slug' => $vendor->slug,
            'users' => $vendor->users->map(fn ($user) => [
                'value' => (string) $user->id,
                'label' => [
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]),
        ];

        return Inertia::render('Vendors/Edit', [
            'vendor' => $formattedVendor,
            'users' => $users,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreVendorRequest $request, Vendor $vendor): RedirectResponse
    {
        $validated = $request->validated();

        // Update the vendor fields
        $vendor->update(Arr::only($validated, ['name', 'domain', 'slug']));

        // Sync users if present
        if (!empty($validated['users'])) {
            $userIds = collect($validated['users'])->pluck('value')->toArray();
            $vendor->users()->sync($userIds);
        } else {
            $vendor->users()->sync([]);
        }

        return redirect()->route('vendors.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vendor $vendor)
    {
        $vendor->delete();
    }
}
