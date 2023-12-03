<?php

namespace Database\Seeders;

use App\Models\Character;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CharacterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Character::create([
            'user_id' => 1,
            'name' => 'Alex',
            'age' => 28,
            'gender' => 'female',
            'bio' => 'A knowledgeable and friendly librarian with a passion for books and helping others.',
            'occupation' => 'Librarian',
            'traits' => json_encode(['smart', 'funny', 'caring']),
            'interests' => 'Reading, History, Technology',
            'location' => 'Virtual Library',
            'dialogue_style' => 'Polite and informative',
            'status' => 'active',
            'image_url' => 'path/to/alex_image.jpg', // Replace with actual path or URL
            'is_public' => true,
        ]);  //
    }
}
