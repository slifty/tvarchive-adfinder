<?php

namespace AdFinder\Helpers;

use AdFinder\Helpers\Contracts\MatcherContract;
use Log;

class DuplitronMatcher implements MatcherContract
{
    private $http;

    // TODO: CurlHttp sohuld be HttpContract
    function __construct(CurlHttp $http)
    {
        $this->http = $http;
    }


    /**
     * See contract for documentation
     */
    public function addMedia($media)
    {

        // Populate the data
        $media_api_data = [
            "project_id" => env('DUPLITRON_PROJECT_ID'),
            "media_path" => $media->media_path,
            "afpt_path" => $media->afpt_path,
            "external_id" => $media->archive_id
        ];
        $url = env('DUPLITRON_URL')."/media";

        // Run the call
        $api_media = $this->http->post($url, $media_api_data);

        return $api_media;
    }

    /**
     * See contract for documentation
     */
    public function addMediaSubset($api_media, $start, $duration)
    {
        // Populate the data
        $media_api_data = [
            "project_id" => env('DUPLITRON_PROJECT_ID'),
            "base_media_id" => $api_media->id,
            "start" => $start,
            "duration" => $duration
        ];
        $url = env('DUPLITRON_URL')."/media";

        // Run the call
        $api_media = $this->http->post($url, $media_api_data);
        return $api_media;
    }

    /**
     * See contract for documentation
     */
    public function getMediaList($match_type)
    {

        // Set up the API call to look at our project
        $url = env('DUPLITRON_URL')."/media?project_id=".env('DUPLITRON_PROJECT_ID');

        // Add any type filters
        switch($match_type)
        {
            case DuplitronMatcher::MEDIA_CORPUS:
                $url = $url."&matchType=corpus";
                break;
            case DuplitronMatcher::MEDIA_DISTRACTOR:
                $url = $url."&matchType=distractor";
                break;
            case DuplitronMatcher::MEDIA_TARGET:
                $url = $url."&matchType=target";
                break;
            case DuplitronMatcher::MEDIA_POTENTIAL_TARGET:
                $url = $url."&matchType=potential_target";
                break;
            default:
                break;
        }

        return $api_task = $this->http->get($url);
    }


    /**
     * See contract for documentation
     */
    public function getTaskList($status_type)
    {

        // Set up the API call to look at our project
        $url = env('DUPLITRON_URL')."/media_tasks?project_id=".env('DUPLITRON_PROJECT_ID');

        // Add any type filters
        switch($status_type)
        {
            case DuplitronMatcher::STATUS_NEW:
                $url = $url."&status=".DuplitronMatcher::STATUS_NEW;
                break;
            case DuplitronMatcher::STATUS_STARTING:
                $url = $url."&status=".DuplitronMatcher::STATUS_STARTING;
                break;
            case DuplitronMatcher::STATUS_PROCESSING:
                $url = $url."&status=".DuplitronMatcher::STATUS_PROCESSING;
                break;
            case DuplitronMatcher::STATUS_FINISHED:
                $url = $url."&status=".DuplitronMatcher::STATUS_FINISHED;
                break;
            case DuplitronMatcher::STATUS_FAILED:
                $url = $url."&status=".DuplitronMatcher::STATUS_FAILED;
                break;
            default:
                break;
        }

        return $this->http->get($url);
    }

    /**
     * See contract for documentation
     */
    public function getMatches($media_id)
    {
        $url = env('DUPLITRON_URL')."/media/".$media_id."/matches";
        return $this->http->get($url);
    }

    /**
     * See contract for documentation
     */
    public function getMedia($media_id)
    {
        $url = env('DUPLITRON_URL')."/media/".$media_id;
        return $api_task = $this->http->get($url);
    }

    /**
     * See contract for documentation
     */
    public function startTask($api_media, $type, $parameters = array())
    {
        // Populate the data
        $task_api_data = [
            "media_id" => $api_media->id,
            "type" => $type
        ];

        $task_api_data = array_merge($task_api_data, $parameters);

        $url = env('DUPLITRON_URL')."/media_tasks";
        // Run the call
        $api_task = $this->http->post($url, $task_api_data);

        return $api_task;
    }

    /**
     * See contract for documentation
     */
    public function getTask($task)
    {
        // Populate the data
        $url = env('DUPLITRON_URL')."/media_tasks/".$task->id;

        // Run the call
        $api_task = $this->http->get($url);
        return $api_task;
    }

    /**
     * See contract for documentation
     */
    public function resolveTask($task)
    {
        // Keep checking task status until it times out or is finished
        $timeout_counter = 0;
        while($timeout_counter < env('DUPLITRON_API_TIMEOUT') || env('DUPLITRON_API_TIMEOUT') == 0)
        {
            sleep(1);
            $task = $this->getTask($task);
            // Check on the status code
            switch($task->status->code)
            {
                case 1: // Started
                case 2: // Processing
                    break;
                case 3: // Finished
                    return $task;
                    break;
                case -1: // Errored
                    return $task;
                    break;
            }
            // Increment the timeout counter
            $timeout_counter += 1;
        }

        throw new \Exception("DUPLITRON_API_TIMEOUT exceeded");
    }

    /**
     * See contract for documentation
     */
    public function registerCanonicalInstance($canonical_id, $instance_id, $start, $end)
    {
        // Is this pointing to an actual archive ID
        if($canonical_id == "" || $instance_id == "")
            return;

        Log::info("New instance: ".$canonical_id.", ".$instance_id." - ".$start." to ".$end);
        if(env('REGISTER_RESULTS_WITH_ARCHIVE') == "true") {
            $segment_url = "https://archive.org/details/".$instance_id."#start/".$start."/end/".$end;
            $register_instance_url = env("ARCHIVE_API_HOST")."/details/tv?another_ad=1&output=json&url=".urlencode($segment_url)."&ad_id=".urlencode($canonical_id);
            return $this->http->get($register_instance_url);
        }
    }

}
