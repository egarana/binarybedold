<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Unit;
use App\Models\Rate;
use App\Models\Reservation;
use Faker\Factory as Faker;

class LightDummyDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        Rate::truncate();
        Unit::truncate();
        Vendor::truncate();
        User::where('id', '!=', 1)->forceDelete();
        Reservation::truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $faker = Faker::create();

        // === 1. Create a single user ===
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'johndoe@gmail.com',
            'password' => Hash::make('password'),
        ]);

        // === 2. Define Vendors, Units, and Rates ===
        $vendorsData = [
            [
                'name' => 'Rimba Luna',
                'domain' => 'default.binarybed.com',
                'slug' => 'default',
                'primary_color'   => 'hsl(0 0% 9%)',
                'secondary_color' => 'hsl(0 0% 92.1%)',
                'accent_color'    => 'hsl(0 0% 96.1%)',
                'units' => [
                    ['name' => 'Deluxe Garden Cottage', 'qty' => 5, 'rate' => 550000, 'type' => 'cottage'],
                    ['name' => 'Premium Horizon Suite', 'qty' => 5, 'rate' => 850000, 'type' => 'suite'],
                    ['name' => 'Family Pool Villa', 'qty' => 2, 'rate' => 1200000, 'type' => 'villa'],
                ],
            ],
            [
                'name' => 'Lake Batur Cabin',
                'domain' => 'lakebaturcabin.com',
                'slug' => 'lakebaturcabin',
                'primary_color'   => 'hsl(12 76% 61%)',
                'secondary_color' => 'hsl(173 58% 39%)',
                'accent_color'    => 'hsl(27 87% 67%)',
                'units' => [
                    ['name' => 'Rahajeng Cabin', 'qty' => 1, 'rate' => 350000, 'type' => 'cabin'],
                    ['name' => 'Rahayu Cabin',  'qty' => 1, 'rate' => 700000, 'type' => 'cabin'],
                ],
            ],
            [
                'name' => 'Natural View Sidemen',
                'domain' => 'naturalviewsidemen.com',
                'slug' => 'naturalviewsidemen',
                'primary_color'   => 'hsl(197 37% 24%)',
                'secondary_color' => 'hsl(43 74% 66%)',
                'accent_color'    => 'hsl(340 75% 55%)',
                'units' => [
                    ['name' => 'Cottage with Garden View', 'qty' => 5, 'rate' => 550000, 'type' => 'cottage'],
                ],
            ],
            [
                'name' => 'Amaya Villas',
                'domain' => 'amayavillas.binarybed.com',
                'slug' => 'amayavillas',
                'primary_color'   => 'hsl(0 0% 9%)',
                'secondary_color' => 'hsl(0 0% 92.1%)',
                'accent_color'    => 'hsl(0 0% 96.1%)',
                'units' => [
                    ['name' => 'Villa Imbuh', 'qty' => 1, 'rate' => 550000, 'type' => 'villa'],
                    ['name' => 'Villa Marie', 'qty' => 1, 'rate' => 850000, 'type' => 'villa'],
                    ['name' => 'Villa Léa', 'qty' => 2, 'rate' => 1200000, 'type' => 'villa'],
                    ['name' => 'Villa Bunga Lily', 'qty' => 2, 'rate' => 1200000, 'type' => 'villa'],
                    ['name' => 'Villa Saraswati Lovina', 'qty' => 2, 'rate' => 1200000, 'type' => 'villa'],
                ],
            ],
        ];

        // === 3. Insert Vendors, Units, and Rates ===
        foreach ($vendorsData as $vendorData) {
            $vendor = Vendor::create([
                'name'              => $vendorData['name'],
                'domain'            => $vendorData['domain'],
                'slug'              => $vendorData['slug'],
                'primary_color'     => $vendorData['primary_color'],
                'secondary_color'   => $vendorData['secondary_color'],
                'accent_color'      => $vendorData['accent_color'],
            ]);
            $vendor->users()->attach($user->id);

            foreach ($vendorData['units'] as $unitData) {
                $features = collect(range(1, 36))
                    ->random(rand(5, 10))
                    ->values()
                    ->toArray();

                // $unit = Unit::create([
                //     'vendor_id' => $vendor->id,
                //     'name'        => $unitData['name'],
                //     'qty'         => $unitData['qty'],
                //     'features'    => $features,
                // ]);

                $unit = Unit::create([
                    'vendor_id'   => $vendor->id,
                    'name'          => $unitData['name'],
                    'slug'          => \Str::slug($unitData['name']), // ✅ matches migration
                    'description'   => $faker->paragraph,             // ✅ optional description
                    'qty'           => $unitData['qty'],
                    'type'          => $unitData['type'],
                    'size'          => $faker->numberBetween(20, 120), // ✅ random size (sqm)
                    'bed_size'      => $faker->randomElement(['Single', 'Queen', 'King', 'Twin']),
                    'view'          => $faker->randomElement(['Garden', 'Pool', 'Ocean', 'Mountain', 'Ricefield']),
                    'occupancy'     => $faker->numberBetween(1, 6),
                    'free_breakfast'=> $faker->boolean(70),            // 70% chance includes breakfast
                    'features'      => $features,
                ]);

                $rate = Rate::create([
                    'unit_id' => $unit->id,
                    'name'    => 'Standard Rate',
                    'price'   => $unitData['rate'],
                ]);

                // // === 4. Create Reservations ===
                // $startDate = new \DateTime('now');
                // $endDate   = (clone $startDate)->modify('+30 days');
                // $datePeriod = new \DatePeriod($startDate, new \DateInterval('P1D'), $endDate);

                // foreach ($datePeriod as $date) {
                //     if (rand(0, 100) < 30) { // 30% chance the unit has no bookings
                //         continue;
                //     }

                //     $reservationsToday = rand(1, $unit->qty);
                //     for ($i = 0; $i < $reservationsToday; $i++) {
                //         $checkIn  = clone $date;
                //         $checkOut = (clone $checkIn)->modify('+' . rand(1, 3) . ' days');
                //         $bookedOn = $faker->dateTimeBetween('-10 days', $checkIn);

                //         Reservation::create([
                //             'unit_id'    => $unit->id,
                //             'rate_id'    => $rate->id,
                //             'first_name' => $faker->firstName,
                //             'last_name'  => $faker->lastName,
                //             'email'      => $faker->unique()->safeEmail,
                //             'phone'      => $faker->phoneNumber,
                //             'check_in'   => $checkIn->format('Y-m-d'),
                //             'check_out'  => $checkOut->format('Y-m-d'),
                //             'booked_on'  => $bookedOn->format('Y-m-d H:i:s'),
                //         ]);
                //     }
                // }
            }
        }
    }
}
