<?php

namespace AdFinder\Jobs;

use AdFinder\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;

use AdFinder\Media;

use AdFinder\Helpers\Contracts\MatcherContract;

class ProcessDistractor extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $media;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($media)
    {
        $this->media = $media;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(MatcherContract $matcher)
    {
        // Mark this media as processing
        $this->media->status = Media::STATUS_PROCESSING;
        $this->media->save();

        $duplitron_media = $matcher->getMedia($this->media->duplitron_id);

        // Update the media to reflect the duplitron's values
        // TODO: this should be done when the media is created, not in this process
        $this->media->archive_id = $duplitron_media->external_id;
        $this->media->media_path = $duplitron_media->media_path;
        $this->media->save();

        // Step 1: Register this as a distractor
        $register_task = $matcher->startTask($duplitron_media, MatcherContract::TASK_ADD_DISTRACTOR);
        $register_task = $matcher->resolveTask($register_task);

        // Step 2: Deregister this as a potential target
        $deregister_task = $matcher->startTask($duplitron_media, MatcherContract::TASK_REMOVE_POTENTIAL_TARGET);
        $deregister_task = $matcher->resolveTask($deregister_task);

        // Step 3: Run a match
        $match_task = $matcher->startTask($duplitron_media, MatcherContract::TASK_MATCH);
        $match_task = $matcher->resolveTask($match_task);

        // Step 4: Remove any matched media in the potential targets list
        $potential_targets = $match_task->result->data->matches->potential_targets;
        foreach($potential_targets as $potential_target)
        {
            // Deregister the potential target
            $matched_media = $potential_target->destination_media;

            // First make sure the match is significant
            $match_duration = $potential_target->duration;
            $matched_media_duration = $matched_media->duration;
            if($match_duration / $matched_media_duration < .6)
                continue;

            $deregister_task = $matcher->startTask($matched_media, MatcherContract::TASK_REMOVE_POTENTIAL_TARGET);
            $deregister_task = $matcher->resolveTask($deregister_task);
        }

        // Mark this media as processed
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
