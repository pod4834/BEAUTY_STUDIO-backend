<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => '테스트 유저',
            'email' => 'test@cellvia.com',
            'password' => Hash::make('password123'), // 비밀번호는 암호화해서 저장!
        ]);
    }
}