<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $userIds = User::where('role', '!=', 'admin')->pluck('id')->all();

        foreach (Post::all() as $post) {
            $num = rand(0, 5);
            for ($i = 0; $i < $num; $i++) {
                Comment::factory()
                    ->for($post)
                    ->create([
                        'user_id' => fake()->randomElement($userIds),
                    ]);
            }
        }
    }
}
