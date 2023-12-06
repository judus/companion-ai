<?php

// In app/Console/Commands/ScaleImages.php

namespace App\Console\Commands;

use App\Jobs\CreateRandomCharacter;
use App\Services\CharacterService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class RandomCharacer extends Command
{
    protected $signature = 'character:random';
    protected $description = 'Create a random character.';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Creating a random character...');
        dispatch(new CreateRandomCharacter());
        $this->info('Job "CreateRandomCharacter dispatched.');
    }

}
