<?php

namespace App\Jobs;

use App\Models\VirtualTourScene;
use App\Services\VirtualTour\VirtualTourImageProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessVirtualTourSceneImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $sceneId;
    public $timeout = 240;
    public $tries = 2;

    public function __construct($sceneId)
    {
        $this->sceneId = $sceneId;
    }

    public function handle(VirtualTourImageProcessor $processor)
    {
        $scene = VirtualTourScene::findOrFail($this->sceneId);
        $processor->process($scene);
    }
}
