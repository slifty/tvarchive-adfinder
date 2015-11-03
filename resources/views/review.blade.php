
@extends('layouts.app')

@section('head')

@endsection

@section('content')

<iframe width="640" height="480" frameborder="0" allowfullscreen src="https://archive.org/embed/{{ $archive_id }}?start={{ $start }}&end={{ $end }}" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>

@endsection



