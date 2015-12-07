<?php
namespace AdFinder\Http\Controllers;

use AdFinder\Http\Controllers\Controller;
use AdFinder\Helpers\Contracts\MatcherContract;
use AdFinder\Helpers\Contracts\HttpContract;

use AdFinder\Media;

use AdFinder\Jobs\IngestVideo;
use AdFinder\Jobs\ProcessDistractor;

class DuplitronController extends Controller {

    /**
     * Get a list of new media and run them through the matcher
     * @return [type] [description]
     */
    public function runMatchJob(MatcherContract $matcher, HttpContract $http)
    {
        $media_list = $this->getNewMedia($http);

        // Create an ingestion job for each item
        foreach($media_list as $input_media)
        {
            // Skip items that have already been processed
            if($this->isAlreadyProcessed($input_media['external_id']))
                continue;

            // Create a new media item for new items
            $media = new Media();
            $media->archive_id = $input_media['external_id'];
            $media->path = $input_media['path'];
            $media->status = Media::STATUS_PENDING;
            $media->process = "ingest";
            $media->save();

            // Dispatch the new job
            $this->dispatch(new IngestVideo($media));
        }

        // Return the list of media
        return $media_list;
    }

    public function getPotentialTargets(MatcherContract $matcher)
    {
        $potential_targets = $matcher->getMediaList(MatcherContract::MEDIA_POTENTIAL_TARGET);

        // Get a list of media being processed
        $results = Media::query();
        $results = $results->where('status', Media::STATUS_PENDING)
            ->orWhere('status', Media::STATUS_PROCESSING);
        $media = $results->get()->toArray();

        // Extract the id's from the media
        $processing_ids = array_map(
            function($result)
            {
                return $result['duplitron_id'];
            },
            $media
        );

        // Remove any potential targets being processed
        $final_targets = array();
        foreach($potential_targets as $potential_target)
        {
            if(in_array($potential_target->id, $processing_ids))
                continue;
            $final_targets[] = $potential_target;
        }
        return $final_targets;
    }

    public function getActiveTasks(MatcherContract $matcher)
    {
        $tasks = array_merge(
            $matcher->getTaskList(MatcherContract::STATUS_NEW),
            $matcher->getTaskList(MatcherContract::STATUS_STARTING),
            $matcher->getTaskList(MatcherContract::STATUS_PROCESSING)
        );
        return $tasks;
    }

    public function getFailedTasks(MatcherContract $matcher)
    {
        $tasks = array_merge(
            $matcher->getTaskList(MatcherContract::STATUS_FAILED)
        );
        return $tasks;
    }

    public function getMatches(MatcherContract $matcher, $media_id)
    {

        // Run a match to ensure the match list is most recent
        // $media = $matcher->getMedia($media_id);
        // $match_task = $matcher->startTask($media, MatcherContract::TASK_MATCH);
        // $match_task = $matcher->resolveTask($match_task);

        return $matcher->getMatches($media_id);
    }

    /**
     * For a given media ID, register it as a distractor and remove it from the list of potential targets
     * @param  MatcherContract $matcher  The matcher interface we are using
     * @param  HttpContract    $http
     * @param  integer         $media_id The ID of the media we want to work with
     * @return [type]                    [description]
     */
    public function registerDistractor($duplitron_id)
    {
        // Load the media with this duplitron ID
        $media = $this->getOrCreateMedia($duplitron_id);
        $media->status = Media::STATUS_PENDING;
        $media->process = "distractor";
        $media->save();

        $this->dispatch(new ProcessDistractor($media));
    }


    /**
     * For a given media ID, register it as a canonical ID and remove it from the list of potential targets
     * @param  MatcherContract $matcher  The matcher interface we are using
     * @param  HttpContract    $http
     * @param  integer         $media_id The ID of the media we want to work with
     * @return [type]                    [description]
     */
    public function registerCanonical($media_id)
    {
        $media = $this->getOrCreateMedia($duplitron_id);
        $media->status = Media::STATUS_PENDING;
        $media->process = "canonical";
        $media->save();
        $this->dispatch(new ProcessCanonical($media, true));
    }

    /**
     * Finds (or creates) media object from a duplitron ID
     * @param  [type] $duplitron_id [description]
     * @return [type]               [description]
     */
    private function getOrCreateMedia($duplitron_id)
    {
        $media = Media::where('duplitron_id', $duplitron_id)->get()->pop();

        // If the media doesn't exist, make it
        if(!$media)
        {
            $media = new Media();
            $media->duplitron_id = $duplitron_id;
        }
        $media->save();
        return $media;
    }

    /**
     * Finds (or creates) media object from a duplitron ID
     * @param  [type] $duplitron_id [description]
     * @return [type]               [description]
     */
    private function isAlreadyProcessed($archive_id)
    {
        $media = Media::where('archive_id', $archive_id)->get()->pop();

        // If the media doesn't exist, return false
        if(!$media)
            return false;
        return true;
    }


    /**
     * Get a list of the latest media
     */
    private function getNewMedia(HttpContract $http)
    {
        // Get a list of recent identifiers
        $files = $http->get(env("ARCHIVE_MEDIA_API"));

        // Create a media item for each file in the list
        array_walk($files, function(&$item, $key) {
            $item = [
                "path" => "http://archive.org/download/".$item."/format=MP3",
                "external_id" => $item
            ];
        });

        return $files;
    }


}

?>
