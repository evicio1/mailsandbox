<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            PlanSeeder::class,
        ]);

        // Primary Super Admin
        User::firstOrCreate(
            ['email' => 'hello@skilleyez.io'],
            [
                'name'           => 'Tharaka',
                'password'       => bcrypt('password'),
                'is_super_admin' => true,
            ]
        );
    }
}
