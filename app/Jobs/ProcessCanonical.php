<?php

namespace AdFinder\Jobs;

use AdFinder\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessCanonical extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $media_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($media_id, $create_new)
    {
        $this->media_id = $media_id;
        $this->is_new = $is_new
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
    }
}
