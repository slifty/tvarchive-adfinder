<?php

use AdFinder\Helpers\Contracts\MatcherContract;

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This file contains all routes used in the ad discovery application
|
*/

// Home page
Route::get('/', function () {
    return view('home');
});


// Logs page
Route::get('/logs', function () {

});

// Review list
Route::get('/list', function () {
    return view('list');
});

// Review media
Route::get('/review/{media_id}', function (MatcherContract $matcher, $id) {

    $media = $matcher->getMedia($id);
    return view('review', [
        'media_id' => $media->id,
        'archive_id' => $media->external_id,
        'end' => $media->start + $media->duration,
        'start' => $media->start]);
});

// Select canonical
Route::get('/canonical/{media_id}', function (MatcherContract $matcher, $id) {

    $media = $matcher->getMedia($id);
    return view('canonical', [
        'media_id' => $media->id,
        'archive_id' => $media->external_id,
        'end' => $media->start + $media->duration,
        'start' => $media->start]);
});


////////////////
/// API Calls

// List of potential targets
Route::get('/api/potential_targets', 'DuplitronController@getPotentialTargets');

// Run the matching algorithm
Route::get('/api/run_matcher', 'DuplitronController@runMatchJob');

// Register an item as being a distractor item
Route::post('/api/register_distractor/{media_id}', 'DuplitronController@registerDistractor');

// Get a list of matches for a media ID
Route::get('/api/matches/{media_id}', 'DuplitronController@getMatches');
