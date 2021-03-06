<?php
namespace AdFinder\Http\Controllers;

use AdFinder\Http\Controllers\Controller;
use AdFinder\Helpers\Contracts\MatcherContract;
use AdFinder\Helpers\Contracts\HttpContract;

use AdFinder\Media;

use AdFinder\Jobs\IngestVideo;
use AdFinder\Jobs\ProcessCanonical;
use AdFinder\Jobs\ProcessDistractor;

class DuplitronController extends Controller {

    /**
     * Get a list of new canonical ads and add them as targets through the matcher
     * @param  MatcherContract $matcher [description]
     * @param  HttpContract    $http    [description]
     * @return [type]                   [description]
     */
    public function runTargetJob(MatcherContract $matcher, HttpContract $http)
    {
        $ad_list = $this->getNewCanonicals($http);

        // Create an ingestion job for each item
        foreach($ad_list as $input_media)
        {
            // Skip items that have already been processed
            // Unless they are marked as failed
            if($this->isAlreadyProcessed($input_media['external_id'])) {
                $media = $this->getOrCreateMediaByArchiveId($input_media['external_id']);
                if($media->status != Media::STATUS_FAILED)
                    continue;
            }
            $media = $this->getOrCreateMediaByArchiveId($input_media['external_id']);
            $media->archive_id = $input_media['external_id'];
            $media->media_path = $input_media['media_path'];
            $media->afpt_path = $input_media['afpt_path'];
            $media->status = Media::STATUS_PENDING;
            $media->process = "canonical";
            $media->save();

            // Dispatch the new job
            $this->dispatch(new ProcessCanonical($media));
        }
    }

    /**
     * Get a list of new media and run them through the matcher
     * @return [type] [description]
     */
    public function runMatchJob(MatcherContract $matcher, HttpContract $http)
    {
        $media_list = $this->getNewMedia($http);

        // Shuffle the media list to randomize the ingestion order
        //  This is an optimization which should help minimize the creation of extra database parts
        //  Since the duplitron clusters corpus files by date, and threads create additional databases
        //  on file lock
        shuffle($media_list);

        // Create an ingestion job for each item
        foreach($media_list as $input_media)
        {
            // Skip items that have already been processed
            if($this->isAlreadyProcessed($input_media['external_id']))
                continue;

            // Create a new media item for new items
            $media = new Media();
            $media->archive_id = $input_media['external_id'];
            $media->media_path = $input_media['media_path'];
            $media->afpt_path = $input_media['afpt_path'];
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
    public function registerCanonical($duplitron_id)
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
     * @param  [type] $archive_id [description]
     * @return [type]               [description]
     */
    private function getOrCreateMediaByArchiveId($archive_id)
    {
        $media = Media::where('archive_id', $archive_id)->get()->pop();

        // If the media doesn't exist, make it
        if(!$media)
        {
            $media = new Media();
            $media->archive_id = $archive_id;
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
        // We only want shows from the past 2 months (for scalability)
        $current_month = date('Y-m', strtotime('-1 months'));

        // Get a list of recent identifiers
        $files = $http->get(env("ARCHIVE_API_HOST")."/details/tv?output=json&weekshows=1&audmonth=".$current_month."&months=2");
        $files = $this->packageMediaForIngestion($files);

        return $files;
    }


    /**
     * Get a list of the latest media
     */
    private function getNewCanonicals(HttpContract $http)
    {
        // Get a list of ad instances from the archive
        // TODO: this should be done completely differently (ideally with a designated endpoint, not a random solr search)
        $url = env('ARCHIVE_API_HOST').'/details/tv?output=json&canonical_ads';
        $full_ads = $http->get($url);

        // Temporary -- we want to strip out ads for the PA 2014 elections
        $ads = array();
        foreach($full_ads as $ad)
        {
            if(strpos($ad, 'TomCorbett') === false
            && strpos($ad, 'TomWolf') === false
            && strpos($ad, 'TomMcgarrigle') === false
            && strpos($ad, 'MikeFitzpatrick') === false
            && strpos($ad, 'AimeeBelgard') === false
            && strpos($ad, 'DonaldNorcross') === false
            && strpos($ad, 'ChrisCoons') === false
            && strpos($ad, 'JohnKane') === false
            && strpos($ad, 'FrankLobiondo') === false
            && strpos($ad, 'HeatherSimmons') === false
            && strpos($ad, 'MananTrivedi') === false
            && strpos($ad, 'MikeSchmidt') === false
            && strpos($ad, 'MarioScavello') === false
            && strpos($ad, 'RyanCostello') === false
            && strpos($ad, 'TomMacarthur') === false
            && strpos($ad, 'TommyTomlinson') === false
            && strpos($ad, 'TomQuigley') === false
            && strpos($ad, 'WarrenKampf') === false)
                $ads[] = $ad;
        }

        $ads = $this->packageMediaForIngestion($ads);
        return $ads;
    }

    /**
     * Take a list of archive ID strings and convert them to full objects
     * @param  array $files A list of archive identifiers (strings)
     * @return array        A list of media objects
     */
    private function packageMediaForIngestion($files) {

        if(!is_array($files))
            return array();

        // Create a media item for each file in the list
        array_walk($files, function(&$item, $key) {
            $item = [
                "media_path" => "https://archive.org/download/".$item."/format=MP3",
                "afpt_path" => "https://archive.org/compress/".$item."/formats=COLUMBIA%20SPARSE%20FINGERPRINT%20TV&file=/".$item.".zip",
                "external_id" => $item
            ];
        });

        return $files;
    }
}

?>
