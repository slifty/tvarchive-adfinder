@extends('layouts.app')

@section('head')

<link rel="stylesheet" type="text/css" href="css/lib/datatables.css"/>

<script type="text/javascript" src="javascript/lib/datatables.min.js"></script>

<script type="text/javascript">

    $(function() {

        // AdFinder tasks
        $.ajax({
            'url': 'api/media',
            'method': 'GET',
            'dataType': 'json'
        })
        .done(function(data) {
            var tasks = [];
            for(var x in data) {
                var task = [
                    data[x]['duplitron_id'],
                    "<a href='" + data[x]['path'] + "' target='_blank'>" + data[x]['archive_id'] + "</a>",
                    data[x]['status'],
                    data[x]['process'],
                    data[x]['updated_at'],
                    data[x]['created_at']
                ]
                tasks.push(task);
            }
            var dtParams = {
                data: tasks,
                columns: [
                    { title: "Media ID" },
                    { title: "Archive ID" },
                    { title: "Status" },
                    { title: "Process" },
                    { title: "Updated" },
                    { title: "Created" }
                ]
            };
            $potential_targets = $("#adfinder_tasks").DataTable(dtParams);
        })

        // Duplitron active tasks
        $.ajax({
            'url': 'api/duplitron_active_tasks',
            'method': 'GET',
            'dataType': 'json'
        })
        .done(function(data) {
            var tasks = [];
            for(var x in data) {
                var task = [
                    data[x]['id'],
                    data[x]['type'],
                    data[x]['start'],
                    data[x]['duration'],
                    data[x]['external_id'],
                    data[x]['media_id'],
                    data[x]['status_code'],
                    data[x]['updated_at'],
                    data[x]['created_at']
                ]
                tasks.push(task);
            }
            var dtParams = {
                data: tasks,
                columns: [
                    { title: "Task ID" },
                    { title: "Type" },
                    { title: "Start" },
                    { title: "Duration" },
                    { title: "Archive ID" },
                    { title: "Duplitron ID" },
                    { title: "Status" },
                    { title: "Updated" },
                    { title: "Created" }
                ]
            };
            $potential_targets = $("#duplitron_active_tasks").DataTable(dtParams);
        })

        // Duplitron failed tasks
        $.ajax({
            'url': 'api/duplitron_failed_tasks',
            'method': 'GET',
            'dataType': 'json'
        })
        .done(function(data) {
            var tasks = [];
            for(var x in data) {
                var task = [
                    data[x]['id'],
                    data[x]['type'],
                    data[x]['start'],
                    data[x]['duration'],
                    data[x]['external_id'],
                    data[x]['media_id'],
                    data[x]['status_code'],
                    data[x]['updated_at'],
                    data[x]['created_at']
                ]
                tasks.push(task);
            }
            var dtParams = {
                data: tasks,
                columns: [
                    { title: "Task ID" },
                    { title: "Type" },
                    { title: "Start" },
                    { title: "Duration" },
                    { title: "Archive ID" },
                    { title: "Duplitron ID" },
                    { title: "Status" },
                    { title: "Updated" },
                    { title: "Created" }
                ]
            };
            $potential_targets = $("#duplitron_failed_tasks").DataTable(dtParams);
        })
    });
</script>

@endsection


@section('content')

<h1>AdFinder</h1>
<h2>All AdFinder Tasks</h2>
<table id="adfinder_tasks" class="display" width="100%"></table>

<h1>Duplitron</h1>
<h2>Active Duplitron Tasks</h2>
<table id="duplitron_active_tasks" class="display" width="100%"></table>

<h2>Failed Duplitron Tasks</h2>
<table id="duplitron_failed_tasks" class="display" width="100%"></table>

@endsection
