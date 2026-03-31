<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRoleId = Role::query()->where('name', 'admin')->value('id');
        $managerRoleId = Role::query()->where('name', 'manager')->value('id');

        User::query()->firstOrCreate([
            'email' => env('ADMIN_EMAIL', 'crtdakhla01@gmail.com'),
        ],[
            'name' => env('ADMIN_NAME', 'NGO Admin'),
            'password' => Hash::make(env('ADMIN_PASSWORD', 'password')),
            'role_id' => $adminRoleId,
        ]);

        $users = [
            [
                'name' => 'Demo Volunteer',
                'email' => 'volunteer@ngo.local',
                'role_id' => $managerRoleId,
            ],
            [
                'name' => 'Demo Partner',
                'email' => 'partner@ngo.local',
                'role_id' => $managerRoleId,
            ],
        ];

        foreach ($users as $user) {
            User::query()->firstOrCreate([
                'email' => $user['email'],
            ], [
                'name' => $user['name'],
                'password' => Hash::make('password'),
                'role_id' => $user['role_id'],
            ]);
        }
    }
}
