<?php

namespace AdFinder\Helpers;

use AdFinder\Helpers\Contracts\MatcherContract;

class DuplitronMatcher implements MatcherContract
{

     // TODO: move these to a .env
    const API_URL = "http://localhost/tvarchive-fingerprinting/public/api";
    const PROJECT_ID = 1;
    const API_TIMEOUT = 1;

    // Task Types
    const TASK_MATCH = "match";
    const TASK_ADD_CORPUS = "corpus_add";
    const TASK_ADD_POTENTIAL_TARGET = "potential_target_add";

    // Match Types
    const MEDIA_CORPUS = "corpus";
    const MEDIA_DISTRACTOR = "distractor";
    const MEDIA_POTENTIAL_TARGET = "potential_target";
    const MEDIA_TARGET = "target";

    /**
     * An internal helper method created in the name of DRY
     * It just calls curl and processes the result
     * @param  string $url  the url to post to
     * @param  object $data the data to send in the post
     * @return object       an object parsed from the json returned
     */
    private function curl_post($url, $data)
    {
        // Create the POST
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        // Take in the server's response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Run the CURL
        $curl_result = curl_exec($ch);
        curl_close ($ch);
        // Parse the result
        $result = json_decode($curl_result);
        return $result;
    }
    /**
     * An internal helper method created in the name of DRY
     * It just calls curl and processes the result
     * @param  string $url  the url to post to
     * @return object       an object parsed from the json returned
     */
    private function curl_get($url)
    {
        // Create the GET
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        // Take in the server's response
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Run the CURL
        $curl_result = curl_exec($ch);
        curl_close ($ch);
        // Parse the result
        $result = json_decode($curl_result);
        return $result;
    }

    /**
     * See contract for documentation
     */
    public function addMedia($path)
    {
        // Populate the data
        $media_api_data = [
            "project_id" => Matcher::PROJECT_ID,
            "media_path" => $path
        ];
        $url = Matcher::API_URL."/media";
        // Run the call
        $api_media = $this->curl_post($url, $media_api_data);
        return $api_media;
    }

    /**
     * See contract for documentation
     */
    public function addMediaSubset($api_media, $start, $duration)
    {
        // Populate the data
        $media_api_data = [
            "project_id" => Matcher::PROJECT_ID,
            "base_media_id" => $api_media->id,
            "start" => $start,
            "duration" => $duration
        ];
        $url = Matcher::API_URL."/media";
        // Run the call
        $api_media = $this->curl_post($url, $media_api_data);
        return $api_media;
    }

    /**
     * See contract for documentation
     */
    public function getMedia($matchType)
    {
        switch($matchType)
        {
            case Matcher::MEDIA_CORPUS:
                break;
            case Matcher::MEDIA_DISTRACTOR:
                break;
            case Matcher::MEDIA_TARGET:
                break;
            case Matcher::MEDIA_POTENTIAL_TARGET:
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
        $url = Matcher::API_URL."/tasks";
        // Run the call
        $api_task = $this->curl_post($url, $task_api_data);
        return $api_task;
    }

    /**
     * See contract for documentation
     */
    public function getTask($task)
    {
        // Populate the data
        $url = Matcher::API_URL."/tasks/".$task->id;
        // Run the call
        $api_task = $this->curl_get($url);
        return $api_task;
    }

    /**
     * See contract for documentation
     */
    public function resolveTask($task)
    {
        // Keep checking task status until it times out or is finished
        $timeout_counter = 0;
        while($timeout_counter < Matcher::API_TIMEOUT)
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
