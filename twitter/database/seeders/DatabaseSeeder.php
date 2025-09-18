<?php

namespace Database\Seeders;


use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
       User::factory()->admin()->create([
            'name' => 'Admin',
            'email' => 'admin@mail.com',
        ]);

        User::factory(20)->regular()->create();

        $this->call([
            FollowSeeder::class,
            PostSeeder::class,
            CommentSeeder::class,
        ]);
    }
}
