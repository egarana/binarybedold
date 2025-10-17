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
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class DummyDataSeeder extends Seeder
{
    public function run(): void
    {
        // Disable FK checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Rate::truncate();
        Unit::truncate();
        Vendor::truncate();
        Reservation::truncate();
        User::where('id', '!=', 1)->forceDelete();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $faker = Faker::create();

        // === 1. Create Multiple Users ===
        $userNames = [
            'Made Santosa', 'John Peterson', 'Ayu Lestari', 'Clara Mitchell', 'Gede Wirawan', 'Sophia Chan',
            'Budi Hartono', 'Emma Wilson', 'Ketut Adi', 'Liam Brown', 'Nyoman Sari', 'Olivia Davis',
            'Wayan Putra', 'Maya Chen', 'Alexander White', 'Rizky Pratama', 'Charlotte Lee', 'Isabella Moore',
            'Kadek Surya', 'William Scott', 'Agung Dharma', 'Emily Carter', 'James Turner', 'Benjamin King',
            'Sarah Johnson', 'Michael Tan', 'Jessica Lim', 'Teguh Santoso', 'Natalie Young', 'Christopher Kim'
        ];

        $users = collect();
        foreach ($userNames as $name) {
            $email = strtolower(str_replace(' ', '.', $name)) . '@gmail.com';
            $users->push(User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make('password'),
            ]));
        }

        // === 2. Vendor Data (more structured like LightDummyDataSeeder) ===
        $vendorNames = [
            'Rimba Luna', 'Lake Batur Cabin', 'Natural View Sidemen', 'Amaya Villas',
            'Uluwatu Cliffside Villa', 'Canggu Sunset Guesthouse', 'Lovina Dolphin Resort',
            'Sidemen Rice Terrace Lodge', 'Bukit Sunrise Villa', 'Tegallalang Jungle Retreat',
            'Kintamani Lakefront Villa', 'Bingin Surf Bungalows', 'Pemuteran Coral Resort'
        ];

        $colorPalettes = [
            ['hsl(0 0% 9%)', 'hsl(0 0% 92.1%)', 'hsl(0 0% 96.1%)'],
            ['hsl(12 76% 61%)', 'hsl(173 58% 39%)', 'hsl(27 87% 67%)'],
            ['hsl(197 37% 24%)', 'hsl(43 74% 66%)', 'hsl(340 75% 55%)'],
            ['hsl(202 83% 41%)', 'hsl(51 95% 63%)', 'hsl(351 84% 50%)'],
        ];

        // Helper for realistic prices
        $rateOptions = [
            ['name' => 'Standard Rate', 'min' => 400000, 'max' => 700000],
            ['name' => 'Weekend Special', 'min' => 450000, 'max' => 750000],
            ['name' => 'Honeymoon Special', 'min' => 800000, 'max' => 1200000],
            ['name' => 'Luxury Escape', 'min' => 1500000, 'max' => 2500000],
            ['name' => 'Family Package', 'min' => 900000, 'max' => 1500000],
        ];

        function nicePrice($min, $max, $step = 50000) {
            $range = range($min, $max, $step);
            return $range[array_rand($range)];
        }

        $unitTypes = ['villa', 'suite', 'cottage', 'bungalow', 'room', 'cabin'];

        // === 3. Generate Vendors, Units, and Rates ===
        foreach ($vendorNames as $index => $vendorName) {
            $colors = $colorPalettes[$index % count($colorPalettes)];
            $vendor = Vendor::create([
                'name' => $vendorName,
                'domain' => strtolower(str_replace(' ', '', preg_replace('/[^A-Za-z0-9 ]/', '', $vendorName))) . '.com',
                'slug' => Str::slug($vendorName),
                'primary_color' => $colors[0],
                'secondary_color' => $colors[1],
                'accent_color' => $colors[2],
            ]);

            // Attach random users (3–5 per vendor)
            $vendor->users()->attach($users->random(rand(3, 5))->pluck('id')->toArray());

            // === Units per Vendor ===
            $unitCount = rand(3, 5);
            for ($u = 0; $u < $unitCount; $u++) {
                $unitName = ucfirst($faker->words(rand(2, 3), true));
                $features = collect(range(1, 36))
                    ->random(rand(8, 15))
                    ->values()
                    ->toArray();

                $unit = Unit::create([
                    'vendor_id'     => $vendor->id,
                    'name'          => $unitName,
                    'slug'          => Str::slug($unitName),
                    'description'   => $faker->paragraph,
                    'qty'           => rand(2, 8),
                    'type'          => $faker->randomElement($unitTypes),
                    'size'          => $faker->numberBetween(25, 120),
                    'bed_size'      => $faker->randomElement(['Single', 'Queen', 'King', 'Twin']),
                    'view'          => $faker->randomElement(['Garden', 'Pool', 'Ocean', 'Mountain', 'Ricefield']),
                    'occupancy'     => $faker->numberBetween(1, 6),
                    'free_breakfast'=> $faker->boolean(70),
                    'features'      => $features,
                ]);

                // === Create Rates ===
                $standardRate = collect($rateOptions)->firstWhere('name', 'Standard Rate');
                $mainRate = Rate::create([
                    'unit_id' => $unit->id,
                    'name'    => 'Standard Rate',
                    'price'   => nicePrice($standardRate['min'], $standardRate['max']),
                ]);

                $extraRates = collect($rateOptions)
                    ->where('name', '!=', 'Standard Rate')
                    ->random(rand(1, 2));

                foreach ($extraRates as $rate) {
                    Rate::create([
                        'unit_id' => $unit->id,
                        'name'    => $rate['name'],
                        'price'   => nicePrice($rate['min'], $rate['max']),
                    ]);
                }

                // === Create Reservations (optional but realistic) ===
                $reservationCount = rand(8, 12);
                for ($r = 0; $r < $reservationCount; $r++) {
                    $checkIn = $faker->dateTimeBetween('now', '+90 days');
                    $checkOut = (clone $checkIn)->modify('+' . rand(1, 5) . ' days');
                    $bookedOn = $faker->dateTimeBetween('-30 days', $checkIn);
                    $rate = $unit->rates->random();

                    Reservation::create([
                        'reservation_code' => 'RSV-' . strtoupper(Str::random(6)),
                        'unit_id'    => $unit->id,
                        'rate_id'    => $rate->id,
                        'first_name' => $faker->firstName,
                        'last_name'  => $faker->lastName,
                        'email'      => $faker->unique()->safeEmail,
                        'phone'      => $faker->phoneNumber,
                        'check_in'   => $checkIn->format('Y-m-d'),
                        'check_out'  => $checkOut->format('Y-m-d'),
                        'booked_on'  => $bookedOn->format('Y-m-d H:i:s'),
                    ]);
                }
            }
        }
    }
}
