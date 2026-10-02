<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ProductSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@wjrc.com',
            'role' => 'admin',
            'status' => 'active',
            'last_login_at' => now()->subHours(2),
        ]);

        $tech = User::factory()->create([
            'name' => 'Tech User',
            'email' => 'tech@wjrc.com',
            'role' => 'technician',
            'status' => 'active',
            'last_login_at' => now()->subDay(),
        ]);

        User::factory()->create([
            'name' => 'Cashier User',
            'email' => 'cashier@wjrc.com',
            'role' => 'cashier',
            'status' => 'active',
            'last_login_at' => now()->subDays(3),
        ]);

        Setting::current();

        $surnames = ['Dela Cruz', 'Reyes', 'Santos', 'Bautista', 'Garcia', 'Mendoza', 'Torres', 'Flores', 'Ramos', 'Aquino', 'Villanueva', 'Castillo', 'Fernandez', 'Del Rosario', 'Navarro', 'Gonzales', 'Pascual', 'Domingo', 'Salazar', 'Cruz'];
        $givenNames = ['Juan', 'Maria', 'Jose', 'Ana', 'Pedro', 'Carmen', 'Antonio', 'Luz', 'Ramon', 'Rosa', 'Manuel', 'Elena', 'Francisco', 'Teresa', 'Ricardo', 'Grace', 'Michael', 'Angela', 'Paolo', 'Bianca'];

        $customers = collect(range(1, 60))->map(function (int $i) use ($surnames, $givenNames) {
            return Customer::create([
                'name' => $givenNames[array_rand($givenNames)].' '.$surnames[array_rand($surnames)],
                'contact_no' => '09'.random_int(100000000, 999999999),
                'email' => 'client'.$i.'@example.com',
                'address' => fake()->city().', Philippines',
            ]);
        });

        $this->call(JobOrderSeeder::class);
        $this->call(SaleSeeder::class);
    }
}
