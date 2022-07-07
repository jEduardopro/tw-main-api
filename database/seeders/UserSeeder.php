<?php

namespace Database\Seeders;

use App\Models\Tweet;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $userTest = User::factory()->activated()->create([
            "name" => "john doe",
            "username" => Str::slug("john doe", "_"),
            "email" => "john@example.com",
            "password" => Hash::make("secretTest1")
        ]);

        User::factory()->unverified()->activated()->withPhoneValidated()->count(20)->create();

        $users = User::factory()->activated()->count(20)->create()->each(function ($user){
            Tweet::factory()->count(rand(1, 10))->create(["user_id" => $user->id]);
        });

        User::factory()->deactivated()->count(5)->create();
        User::factory()->reactivated()->count(5)->create();
        User::factory()->deactivated()->withSoftDelete()->count(5)->create();

        User::whereNotIn('id', $users->pluck('id'))->get()->each(function ($user) use ($users) {
            $user->following()->saveMany($users);

            Tweet::factory()->count(rand(1, 10))->create(["user_id" => $user->id]);
        });

        User::take(20)->where('id', '!=', $userTest->id)->get()->each(function ($user) use ($userTest) {
            $user->following()->attach($userTest);
        });


        $userTest->tweets()->saveMany(Tweet::factory()->count(10)->make(["user_id" => $userTest->id]));
        $userTest->following()->saveMany($users);

        $users->each(function ($u) use ($userTest) {
            $u->following()->attach($userTest->id);
        });

    }
}
