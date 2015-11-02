<?php

namespace AdFinder\Helpers\Contracts;

interface MatcherContract
{

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
     * Add a new piece of media to the system
     * @param string $path the network path to the media
     */
    public function addMedia($path);

    /**
     * Create a subset of existing media in the system
     * @param object $api_media the media object that the subset comes from
     * @param double $start the start time of the media snippet
     * @param double $duration the length of the snippet
     */
    public function addMediaSubset($api_media, $start, $duration);

    /**
     * Get a list of media registered in the system
     * @param  string $matchType the category of media being retrieved
     * @return array(object)    the list of media objects
     */
    public function getMedia($matchType);

    /**
     * Start a matching task
     * @param  object $api_media a media object returned by the API
     * @param  string $type A task type (see the TYPE constants for a list of valid types)
     */
    public function startTask($api_media, $type);


    /**
     * Get a task that has already been created
     * @param  object $task the task object being retrieved
     * @return object the task object
     */
    public function getTask($task);

    /**
     * Tasks are asynchronous, so this method is what makes them synchronous.
     * Pass an existing task and it will return when the task has resolved (or if the task has timed out)
     * @param  object $task The task that we want to complete
     * @return object       The final value for the task
     */
    public function resolveTask($task);

}
