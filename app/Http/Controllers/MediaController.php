<?php

namespace AdFinder\Http\Controllers;

use Illuminate\Http\Request;

use AdFinder\Http\Requests;
use AdFinder\Http\Controllers\Controller;

use AdFinder\Media;

class MediaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index(Request $request, $start, $end)
    {
        $results = Media::query();

        // Filter by status
        if($request->has('status'))
        {
            switch($request->input('status'))
            {
                case Media::STATUS_FAILED:
                    $results = $results->where('status', Media::STATUS_FAILED);
                    break;
                case Media::STATUS_STABLE:
                    $results = $results->where('status', Media::STATUS_STABLE);
                    break;
                case Media::STATUS_PENDING:
                    $results = $results->where('status', Media::STATUS_PENDING);
                    break;
                case Media::STATUS_PROCESSING:
                    $results = $results->where('status', Media::STATUS_PROCESSING);
                    break;
            }
        }

        // TODO: add real pagination
        $results = $results->take(5000);

        return $results->get();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }

}
