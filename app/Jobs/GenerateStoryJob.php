<?php

namespace App\Jobs;

use App\Models\Character;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateStoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(Character $character)
    {
        $this->character = $character;
    }

    public function handle()
    {
        // $this->openAI->generateStory($this->character)
        Log::debug('Handling job "GenerateStoryJob"');
    }
}
