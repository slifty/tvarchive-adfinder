<?php

namespace AdFinder\Jobs;

use AdFinder\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;

use AdFinder\Media;

use AdFinder\Helpers\Contracts\MatcherContract;
use Log;

class ProcessCanonical extends Job implements SelfHandling, ShouldQueue
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

        // Add this media to the duplitron if it hasn't been added already
        if(!$this->media->duplitron_id)
        {
            $duplitron_media = $matcher->addMedia($this->media);
            $this->media->duplitron_id = $duplitron_media->id;
            $this->media->save();
        }

        Log::info("Starting to process: ".$this->media->duplitron_id);

        // Load the latest duplitron media
        $duplitron_media = $matcher->getMedia($this->media->duplitron_id);

        // Update the media to reflect the duplitron's values
        // TODO: this should be done when the media is created, not in this process
        $this->media->archive_id = $duplitron_media->external_id;
        $this->media->media_path = $duplitron_media->media_path;
        $this->media->save();

        // Step 1: Register this as a target
        // ... unless it is already a target
        if(!$duplitron_media->match_categorization->is_target) {
            $register_task = $matcher->startTask($duplitron_media, MatcherContract::TASK_ADD_TARGET);
            $register_task = $matcher->resolveTask($register_task);
        }

        // Step 2: Deregister this as a potential target
        // $deregister_task = $matcher->startTask($duplitron_media, MatcherContract::TASK_REMOVE_POTENTIAL_TARGET);
        // $deregister_task = $matcher->resolveTask($deregister_task);

        // Step 3: Deregister this as a distractor
        // $deregister_task = $matcher->startTask($duplitron_media, MatcherContract::TASK_REMOVE_DISTRACTOR);
        // $deregister_task = $matcher->resolveTask($deregister_task);

        // Step 4: Run a match
        $parameters = array(
            "end_date" => "2016-11-08",
            "start_date" => "2016-10-01"
        );
        $match_task = $matcher->startTask($duplitron_media, MatcherContract::TASK_FULL_MATCH, $parameters);
        $match_task = $matcher->resolveTask($match_task);

        // Before moving forward, make sure we got data back
        if($match_task)
        {

            // Step 5: Look for all instances of this among the corpus
            $instances = $match_task->result->data->matches->corpus;

            // Sort them in order of start time
            // NOTE: right now there is a known bug where audfprint will sometimes have TWO copies of an ad, split up!
            // By inserting in order we will passively drop the duplicate due to overlap on backend
            // Eventually we should make the matches more accurate *and* handle overlap on frontend.
            $target_start_sort = function($a, $b)
            {
                if($a->target_start < $b->target_start)
                    return -1;
                if($a->target_start > $b->target_start)
                    return 1;
                return 0;
            };

            foreach($instances as $instance)
            {
                // Skip matches that are too short
                if($instance->duration < env('DUPLITRON_MIN_DURATION'))
                    continue;

                // Skip matches that are too long
                if($instance->duration > env('DUPLITRON_MAX_DURATION'))
                    continue;

                // Skip matches with a confidence level that is too low
                $confidence = $instance->consecutive_hashes / $instance->common_hashes;
                if($confidence < env('DUPLITRON_MIN_HASH_RATIO'))
                    continue;

                $start = $instance->target_start;
                $end = $start + $instance->duration;
                $canonical_id = $this->media->archive_id;
                $instance_id = $instance->destination_media->external_id;
                $matcher->registerCanonicalInstance($canonical_id, $instance_id, $start, $end);
            }
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
