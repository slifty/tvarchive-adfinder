<?php
namespace AdFinder\Http\Controllers;

use AdFinder\Http\Controllers\Controller;
use AdFinder\Helpers\Contracts\MatcherContract;
use AdFinder\Helpers\Contracts\HttpContract;

class DuplitronController extends Controller {

    /**
     * Get a list of new media and run them through the matcher
     * @return [type] [description]
     */
    public function runMatchJob(MatcherContract $matcher, HttpContract $http)
    {
        $media_list = $this->getNewMedia($http);

        // Send the file to the API
        foreach($media_list as $input_media)
        {
            ///////////
            // Add the media to the API
            $media = $matcher->addMedia($input_media);

            // Run the match command
            $match_task = $matcher->startTask($media, MatcherContract::TASK_MATCH);

            // Wait for the match to finish
            $match_task = $matcher->resolveTask($match_task);

            // Iterate through the matched segments
            $segments = $match_task->result->data->segments;
            foreach($segments as $segment)
            {
                switch($segment->type)
                {
                    case 'distractor':
                        // Skip distractors
                        break;
                    case 'potential_target':
                        // Skip potential targets
                        break;
                    case 'target':
                        // Register target match with the archive
                        // TODO: send this registration call to an archive API endpoint
                        break;
                    case 'corpus':
                        // Create a new media segment for the corpus match
                        $api_media_subset = $matcher->addMediaSubset($media, $segment->start, $segment->end - $segment->start);

                        // Register the segment as a new potential target
                        $store_task = $matcher->startTask($api_media_subset, MatcherContract::TASK_ADD_POTENTIAL_TARGET);
                        $store_task = $matcher->resolveTask($store_task);
                        break;
                }
            }

            // Add media to the corpus
            $matcher->startTask($media, MatcherContract::TASK_ADD_CORPUS);
        }

    }

    public function getPotentialTargets(MatcherContract $matcher)
    {
        return $matcher->getMediaList(MatcherContract::MEDIA_POTENTIAL_TARGET);
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
