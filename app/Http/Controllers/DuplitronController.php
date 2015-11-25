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

            if($match_task->status->code == MatcherContract::STATUS_FAIL)
                continue;

            // Add media to the corpus
            $corpus_task = $matcher->startTask($media, MatcherContract::TASK_ADD_CORPUS);
            $matcher->resolveTask($corpus_task);
            print_r($match_task);

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
                        // Register target match with the archive
                        // TODO: send this registration call to an archive API endpoint
                        break;
                    case 'corpus':
                        // Create a new media segment for the corpus match
                        $api_media_subset = $matcher->addMediaSubset($media, $segment->start, $segment->end - $segment->start);

                        // Register the segment as a new potential target
                        $store_task = $matcher->startTask($api_media_subset, MatcherContract::TASK_ADD_POTENTIAL_TARGET);
                        $store_task = $matcher->resolveTask($store_task);

                        // Run a match to populate the match data for the potential target
                        $match_task = $matcher->startTask($api_media_subset, MatcherContract::TASK_MATCH);
                        $match_task = $matcher->resolveTask($match_task);

                        break;
                }
            }
        }

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
    public function registerDistractor(MatcherContract $matcher, HttpContract $http, $media_id)
    {
        // TODO: All this should be done async in the background
        // TODO: we should support local flagging of which items are under review / being processed

        $media = $matcher->getMedia($media_id);

        // Step 1: Register this as a distractor
        $register_task = $matcher->startTask($media, MatcherContract::TASK_ADD_DISTRACTOR);
        $register_task = $matcher->resolveTask($register_task);

        // Step 2: Deregister this as a potential target
        $deregister_task = $matcher->startTask($media, MatcherContract::TASK_REMOVE_POTENTIAL_TARGET);
        $deregister_task = $matcher->resolveTask($deregister_task);

        // Step 3: Run a match
        $match_task = $matcher->startTask($media, MatcherContract::TASK_MATCH);
        $match_task = $matcher->resolveTask($match_task);

        // Step 4: Remove any matched media in the potential targets list
        $potential_targets = $match_task->result->data->matches->potential_targets;
        foreach($potential_targets as $potential_target)
        {
            // Deregister the potential target
            $matched_media = $potential_target->destination_media;
            $deregister_task = $matcher->startTask($matched_media, MatcherContract::TASK_REMOVE_POTENTIAL_TARGET);
            $deregister_task = $matcher->resolveTask($deregister_task);
        }
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
