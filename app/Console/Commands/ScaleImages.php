<?php

// In app/Console/Commands/ScaleImages.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
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
            $this->resizeImage($file, 120, 120);
            $this->resizeImage($file, 480, 480);
        }

        $this->info('All images have been processed.');
    }

    public function resizeImage($storagePath, $width, $height)
    {
        $image = Image::make(Storage::disk('gcs')->get($storagePath));
        $filename = Str::replaceEnd(".png", ".jpg", basename($storagePath));
        $image->resize($width, $height, function ($constraint) {
            $constraint->aspectRatio();
        });

        Storage::disk('gcs')->put("images/_{$width}x{$height}/{$filename}", $image->encode('jpg'));
    }
}
