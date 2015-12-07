@extends('layouts.app')

@section('head')

<link rel="stylesheet" type="text/css" href="css/lib/datatables.css"/>

<script type="text/javascript" src="javascript/lib/datatables.min.js"></script>

<script type="text/javascript">
    $(function() {
        $.ajax({
            'url': 'api/potential_targets',
            'method': 'GET',
            'dataType': 'json'
        })
        .done(function(data) {
            var potential_targets = [];
            for(var x in data) {
                potential_target = [
                    data[x]['external_id'],
                    data[x]['start'],
                    data[x]['duration'],
                    data[x]['created_at'],
                    "<a href='review/" + data[x]['id'] + "' target='_blank'>Review</a>"
                ]
                potential_targets.push(potential_target);
            }
            $potential_targets = $("#potential_targets").DataTable({
                data: potential_targets,
                columns: [
                    { title: "Archive ID" },
                    { title: "Start" },
                    { title: "Duration" },
                    { title: "Created" },
                    { title: "" }
                ],
                iDisplayLength: 100
            } );
        });
    });
</script>

@endsection


@section('content')

<table id="potential_targets" class="display" width="100%"></table>

@endsection
