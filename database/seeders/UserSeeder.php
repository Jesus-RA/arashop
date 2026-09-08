<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => env('ARASHOP_OWNER_EMAIL', 'ale@arashop.test')],
            [
                'name' => 'Ale',
                'password' => env('ARASHOP_OWNER_PASSWORD', 'password'),
            ],
        );

        User::firstOrCreate(
            ['email' => env('ARASHOP_DEVELOPER_EMAIL', 'dev@arashop.test')],
            [
                'name' => 'Developer',
                'password' => env('ARASHOP_DEVELOPER_PASSWORD', 'password'),
            ],
        );
    }
}
