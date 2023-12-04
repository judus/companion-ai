<?php

// In app/Console/Commands/ScaleImages.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class ScaleImages extends Command
{
    protected $signature = 'images:scale';
    protected $description = 'Scale all images and store them in specific folders';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $files = Storage::disk('gcs')->files('images');

        foreach ($files as $file) {
            $this->info("Processing: $file");

            $image = Image::make(Storage::disk('gcs')->get($file));
            $filename = basename($file);

            // 128x128 variation
            $image->resize(240, 240, function ($constraint) {
                $constraint->aspectRatio();
            });

            Storage::disk('gcs')->put("images/_240x240/{$filename}", $image->encode('png'));

//            // 128x128 variation
//            $image->resize(90, 90);
//            Storage::disk('gcs')->put("images/_90x90/{$filename}", (string)$image->encode('png'));
//
//            // 256x256 variation
//            $image->resize(240, 240);
//            Storage::disk('gcs')->put("images/_240x240/{$filename}", (string)$image->encode('png'));

            // Add more variations as needed
        }

        $this->info('All images have been processed.');
    }
}
