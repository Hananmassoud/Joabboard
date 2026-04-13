<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = strtolower(config('admin.email', 'admin123@gmail.com'));

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name'     => 'Administrator',
                'password' => Hash::make('Admin123'),
                'role'     => 'applicant',
            ]
        );

        DB::table('users')->where('id', $user->id)->update(['is_admin' => true]);
    }
}
