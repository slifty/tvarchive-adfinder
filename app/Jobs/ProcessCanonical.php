<?php

namespace AdFinder\Jobs;

use AdFinder\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;

use AdFinder\Media;

class ProcessCanonical extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $media;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($media, $create_new)
    {
        $this->media = $media;
        $this->is_new = $is_new
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->media->status = Media::STATUS_STABLE;
        $this->media->save();
    }

    /**
     * Handle a job failure.
     *
     * @return void
     */
    public function failed()
    {
        // Called when the job is failing...
        $this->media->status = Media::STATUS_FAILED;
        $this->media->save();
    }
}
