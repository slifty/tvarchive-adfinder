<?php

namespace AdFinder\Helpers;

use AdFinder\Helpers\Contracts\MatcherContract;

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
    public function addMedia($path)
    {

        // Populate the data
        $media_api_data = [
            "project_id" => env('DUPLITRON_PROJECT_ID'),
            "media_path" => $path
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
    public function getMedia($matchType)
    {
        switch($matchType)
        {
            case DuplitronMatcher::MEDIA_CORPUS:
                break;
            case DuplitronMatcher::MEDIA_DISTRACTOR:
                break;
            case DuplitronMatcher::MEDIA_TARGET:
                break;
            case DuplitronMatcher::MEDIA_POTENTIAL_TARGET:
                break;
            default:
                break;
        }

        return [];
    }

    /**
     * See contract for documentation
     */
    public function startTask($api_media, $type)
    {
        // Populate the data
        $task_api_data = [
            "media_id" => $api_media->id,
            "type" => $type
        ];

        $url = env('DUPLITRON_URL')."/tasks";
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
        $url = env('DUPLITRON_URL')."/tasks/".$task->id;
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
        while($timeout_counter < env('DUPLITRON_API_TIMEOUT'))
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
                case -1:
                    break;
            }
            // Increment the timeout counter
            $timeout_counter += 1;
        }
        return $task;
    }

}
