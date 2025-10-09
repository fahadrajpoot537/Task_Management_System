<?php

namespace Database\Seeders;

use App\Models\Channel;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create general channel
        $generalChannel = Channel::create([
            'name' => 'general',
            'description' => 'General discussion channel for all team members',
            'slug' => 'general',
            'is_private' => false,
            'created_by_user_id' => 1, // Assuming user with ID 1 exists
        ]);

        // Create announcements channel
        $announcementsChannel = Channel::create([
            'name' => 'announcements',
            'description' => 'Important announcements and updates',
            'slug' => 'announcements',
            'is_private' => false,
            'created_by_user_id' => 1,
        ]);

        // Create random channel
        $randomChannel = Channel::create([
            'name' => 'random',
            'description' => 'Random discussions and off-topic conversations',
            'slug' => 'random',
            'is_private' => false,
            'created_by_user_id' => 1,
        ]);

        // Add all users to the channels
        $users = User::all();
        
        foreach ($users as $user) {
            $generalChannel->addMember($user);
            $announcementsChannel->addMember($user);
            $randomChannel->addMember($user);
        }

        // Create some sample messages
        if ($users->count() > 0) {
            $generalChannel->messages()->create([
                'user_id' => $users->first()->id,
                'body' => 'Welcome to the general channel! 👋',
            ]);

            $announcementsChannel->messages()->create([
                'user_id' => $users->first()->id,
                'body' => 'Welcome to our task management system! This channel is for important announcements.',
            ]);

            $randomChannel->messages()->create([
                'user_id' => $users->first()->id,
                'body' => 'Feel free to chat about anything here! 🎉',
            ]);
        }
    }
}
