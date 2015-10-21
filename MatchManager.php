<?php

require("Duplitron.php");

// This class is responsible for communicating with the fingerprinting API.
class MatchManager
{

    public function runMatchJob()
    {
        $mediaList = $this->getNewMedia();
        $this->processMedia($mediaList);
    }

    /**
     * Get a list of the latest media
     */

    private function getNewMedia()
    {
        // Load all mp3 files from the samples directory
        // TODO: make this work with the archive, rather than a list of local files
        $path    = 'samples';
        $files = scandir($path);
        $files = array_filter($files, function($item) {
            $ext = pathinfo($item, PATHINFO_EXTENSION);
            if($ext == "mp3")
                return true;
            return false;
        });

        array_walk($files, function(&$item, $key) {
            $item = [
                "path" => "ssh://slifty@127.0.0.1/Users/slifty/Sites/tvarchive-ad_discovery/samples/".$item
            ];
        });

        $files = array_slice($files, 0,2);

        return $files;
    }

    /**
     * For each item in the latest media, run it through the matching API process
     */
    private function processMedia($media_list)
    {
        $duplitron = new Duplitron();


        // Send the file to the API
        foreach($media_list as $input_media)
        {
            ///////////
            // Add the media to the API
            $media = $duplitron->addMedia($input_media['path']);

            // Run the match command
            $match_task = $duplitron->startTask($media, Duplitron::TASK_MATCH);

            echo("Running Match: ".$input_media['path']."<br>");

            // Wait for the match to finish
            $match_task = $duplitron->resolveTask($match_task);


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
                        $api_media_subset = $duplitron->addMediaSubset($media, $segment->start, $segment->end - $segment->start);

                        echo("<br>NEW POTENTIAL TARGET:<br>");
                        print_r($api_media_subset);
                        echo("<br>-----<br>");

                        // Register the segment as a new potential target
                        $store_task = $duplitron->startTask($media, Duplitron::TASK_ADD_POTENTIAL_TARGET);
                        $store_task = $duplitron->resolveTask($store_task);
                        break;
                }
            }

            // Add media to the corpus
            $duplitron->startTask($media, Duplitron::TASK_ADD_CORPUS);

        }
    }
}

?>
