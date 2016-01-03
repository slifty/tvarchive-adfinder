<?php

class Duplitron
{

    // TODO: move these to a .env
    const API_URL = "http://localhost/tvarchive-fingerprinting/public/api";
    const PROJECT_ID = 1;
    const API_TIMEOUT = 1;

    // Task Types
    const TASK_MATCH = "match";
    const TASK_ADD_CORPUS = "corpus_add";
    const TASK_ADD_POTENTIAL_TARGET = "potential_target_add";


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
     * Add a new piece of media to the system
     * @param string $path the network path to the media
     */
    public function addMedia($path)
    {
        // Populate the data
        $media_api_data = [
            "project_id" => Duplitron::PROJECT_ID,
            "media_path" => $path
        ];
        $url = Duplitron::API_URL."/media";

        // Run the call
        $api_media = $this->curl_post($url, $media_api_data);

        return $api_media;
    }

    /**
     * Create a subset of existing media in the system
     * @param object $api_media the media object that the subset comes from
     * @param double $start the start time of the media snippet
     * @param double $duration the length of the snippet
     */
    public function addMediaSubset($api_media, $start, $duration)
    {
        // Populate the data
        $media_api_data = [
            "project_id" => Duplitron::PROJECT_ID,
            "base_media_id" => $api_media->id,
            "start" => $start,
            "duration" => $duration
        ];
        $url = Duplitron::API_URL."/media";

        // Run the call
        $api_media = $this->curl_post($url, $media_api_data);

        return $api_media;
    }

    /**
     * Start a matching task
     * @param  object $api_media a media object returned by the API
     * @param  string $type A task type (see the TYPE constants for a list of valid types)
     */
    public function startTask($api_media, $type)
    {
        // Populate the data
        $task_api_data = [
            "media_id" => $api_media->id,
            "type" => $type
        ];
        $url = Duplitron::API_URL."/tasks";

        // Run the call
        $api_task = $this->curl_post($url, $task_api_data);

        return $api_task;
    }

    public function getTask($task)
    {
        // Populate the data
        $url = Duplitron::API_URL."/tasks/".$task->id;

        // Run the call
        $api_task = $this->curl_get($url);

        return $api_task;
    }

    /**
     * Tasks are asynchronous, so this method is what makes them synchronous.
     * Pass an existing task and it will return when the task has resolved (or if the task has timed out)
     * @param  object $task The task that we want to complete
     * @return object       The final value for the task
     */
    public function resolveTask($task)
    {
        // Keep checking task status until it times out or is finished
        $timeout_counter = 0;
        while($timeout_counter < Duplitron::API_TIMEOUT)
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

?>
