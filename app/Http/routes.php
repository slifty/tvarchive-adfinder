<?php

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


// List of potential targets
Route::get('/potential_targets', function () {

});

// Run the matching algorithm
Route::get('/matcher', 'DuplitronController@runMatchJob');
