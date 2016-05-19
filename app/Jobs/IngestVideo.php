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

        // Add media to the corpus
        // This needs to be done before matching to ensure no comparisons are missed in multithreaded environments
        $corpus_task = $matcher->startTask($duplitron_media, MatcherContract::TASK_ADD_CORPUS);
        $matcher->resolveTask($corpus_task);

        if($corpus_task->status->code == MatcherContract::STATUS_FAILED)
        {
            $this->media->status = Media::STATUS_FAILED;
            $this->media->save();
            return;
        }

        // Match against targets
        $match_task = $matcher->startTask($duplitron_media, MatcherContract::TASK_MATCH_TARGETS);
        $match_task = $matcher->resolveTask($match_task);

        // Run the match command
        // $match_task = $matcher->startTask($duplitron_media, MatcherContract::TASK_MATCH);
        // $match_task = $matcher->resolveTask($match_task);

        // If the task failed, move along
        if($match_task->status->code == MatcherContract::STATUS_FAILED)
        {
            $this->media->status = Media::STATUS_FAILED;
            $this->media->save();
            return;
        }

        // Get the list of target matches
        $matches = $match_task->result->data->matches;
        $targets = $matches->targets;

        // Register the targets
        foreach($targets as $target)
        {
            // Skip matches that are too short
            if($target->duration < env('DUPLITRON_MIN_DURATION'))
                continue;

            // Skip matches that are too long
            if($target->duration < env('DUPLITRON_MAX_DURATION'))
                continue;

            // Skip matches with a confidence level that is too low
            $confidence = $target->consecutive_hashes / $target->duration;
            if($confidence < env('DUPLITRON_MIN_CONFIDENCE'))
                continue;

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
