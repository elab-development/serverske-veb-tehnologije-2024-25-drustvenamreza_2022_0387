<?php

namespace Database\Seeders;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FollowSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIds = User::where('role', '!=', 'admin')->pluck('id')->all();

        foreach ($userIds as $uid) {
            $possible = array_values(array_diff($userIds, [$uid]));
            shuffle($possible);

            $count = rand(3, min(10, count($possible)));
            $targets = array_slice($possible, 0, $count);

            foreach ($targets as $tid) {
                Follow::firstOrCreate([
                    'follower_id' => $uid,
                    'following_id' => $tid,
                ]);
            }
        }
    }
}
