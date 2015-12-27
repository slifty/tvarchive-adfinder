<?php

namespace AdFinder\Jobs;

use AdFinder\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;

use AdFinder\Media;

use AdFinder\Helpers\Contracts\MatcherContract;

class IngestVideo extends Job implements SelfHandling, ShouldQueue
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

        ///////////
        // Add the media to the API
        $duplitron_media = $matcher->addMedia($this->media);
        $this->media->duplitron_id = $duplitron_media->id;
        $this->media->save();

        // Skip any media that is already in the corpus
        if($duplitron_media->match_categorization->is_corpus)
            return;

        // Run the match command
        $match_task = $matcher->startTask($duplitron_media, MatcherContract::TASK_MATCH);

        // Wait for the match to finish
        $match_task = $matcher->resolveTask($match_task);

        // If the task failed, move along
        if($match_task->status->code == MatcherContract::STATUS_FAILED)
        {
            $this->media->status = Media::STATUS_FAILED;
            $this->media->save();
            return;
        }

        // Add media to the corpus
        $corpus_task = $matcher->startTask($duplitron_media, MatcherContract::TASK_ADD_CORPUS);
        $matcher->resolveTask($corpus_task);

        if($corpus_task->status->code == MatcherContract::STATUS_FAILED)
        {
            $this->media->status = Media::STATUS_FAILED;
            $this->media->save();
            return;
        }

        // Iterate through the matched segments
        $segments = $match_task->result->data->segments;
        foreach($segments as $segment)
        {
            // Cut out segments that don't fit our bounds
            $duration = $segment->end - $segment->start;

            if($duration < env('DUPLITRON_MIN_DURATION')
            || $duration > env('DUPLITRON_MAX_DURATION'))
                continue;

            switch($segment->type)
            {
                case 'distractor':
                    // Skip distractors
                    break;
                case 'potential_target':
                    // Skip potential targets
                    break;
                case 'target':
                    // We care about these segments, but register them in the matches loop
                    break;
                case 'corpus':

                    // TODO: this ought to be handled in a separate job
                    // Create a new media segment for the corpus match
                    $api_media_subset = $matcher->addMediaSubset($duplitron_media, $segment->start, $segment->end - $segment->start);

                    // Register the segment as a new potential target
                    $store_task = $matcher->startTask($api_media_subset, MatcherContract::TASK_ADD_POTENTIAL_TARGET);
                    $store_task = $matcher->resolveTask($store_task);

                    // Run a match to populate the match data for the potential target
                    $match_task = $matcher->startTask($api_media_subset, MatcherContract::TASK_MATCH);
                    $match_task = $matcher->resolveTask($match_task);

                    break;
            }
        }

        // Iterate through the target matches and register them
        $targets = $match_task->result->data->matches->targets;
        foreach($targets as $target)
        {
            $start = $target->start;
            $end = $target->start + $target->duration;
            $instance_id = $this->media->archive_id;
            $canonical_id = $target->destination_media->external_id;
            $matcher->registerCanonicalInstance($canonical_id, $instance_id, $start, $end);

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
