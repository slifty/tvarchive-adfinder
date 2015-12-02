<?php
namespace AdFinder\Http\Controllers;

use AdFinder\Http\Controllers\Controller;
use AdFinder\Helpers\Contracts\MatcherContract;
use AdFinder\Helpers\Contracts\HttpContract;

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
            // Dispatch the new job
            $this->dispatch(new IngestVideo($input_media));
        }

        // Return the list of media
        return $media_list;
    }

    public function getPotentialTargets(MatcherContract $matcher)
    {
        return $matcher->getMediaList(MatcherContract::MEDIA_POTENTIAL_TARGET);
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
    public function registerDistractor($media_id)
    {
        // TODO: we should support local flagging of which items are under review / being processed
        $this->dispatch(new ProcessDistractor($media_id));
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
        // TODO: we should support local flagging of which items are under review / being processed
        $this->dispatch(new ProcessCanonical($media_id, true));
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
