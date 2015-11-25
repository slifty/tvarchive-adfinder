
@extends('layouts.app')

@section('head')

<script type="text/javascript">
    $(function() {

        var media_id = {{ $media_id }};

        // Get a list of matches
        $.ajax({
            method: "GET",
            url: "{{url('/')}}/api/matches/" + media_id,
            dataType: "json"
        })
        .always(function(d,e,f) {
            console.log(d);
            console.log(e);
            console.log(f);
        })
        .done(function(d) {
            $canonicals = $("#canonicals");
            $canonicals.html("");

            // Add this copy first (it won't appear in the list)
            var $clip = generateClip("{{ $archive_id }}", {{ $start }}, {{ $end }});
            $canonicals.append($clip);

            for(var x in d) {
                var clip = d[x];
                var duration = parseFloat(clip['duration']);
                var start = 0;
                var end = 0;
                var archive_id = "";

                // Use the source / destination that DOESNT match
                // if(clip['source_id'] == media_id) {
                    console.log("DEST")
                    archive_id = clip['destination']['external_id'];
                    start = parseFloat(clip['destination_start']) + parseFloat(clip['destination']['start']);
                    end = start + duration;
                    $clip = generateClip(archive_id, start, end);
                    $canonicals.append($clip);
                    $canonicals.append("<br>");
                // }
                // else {
                    console.log("SOURCE");
                    archive_id = clip['source']['external_id'];
                    start = parseFloat(clip['source_start']) + parseFloat(clip['source']['start']);
                    end = start + duration;
                    $clip = generateClip(archive_id, start, end);
                    $canonicals.append($clip);
                    $canonicals.append("<hr>");
               // }
            }
        })
    });

    function generateClip(archive_id, start, end) {
        console.log(archive_id + " :: " + start + " " + end);

        var $clip = $("<div>")
            .addClass("clip");

        var embedSrc = "https://archive.org/embed/" + archive_id + "?start=" + start + "&end=" + end
        var $embed = $("<iframe>")
            .attr("width", 400)
            .attr("height", 300)
            .attr("frameborder", 0)
            .attr("src", embedSrc)
            .appendTo($clip);

        var linkSrc = "https://archive.org/details/" + archive_id + "#start/" + start + "/end/" + end;
        var $link = $("<a>")
            .attr("href", linkSrc)
            .text(archive_id + " :: " + start + " " + end)
            .attr("target", "_blank")
            .appendTo($clip);

        return $clip;
    }
</script>

@endsection

@section('content')

<div id="canonicals">Searching for all copies of this clip...</div>

@endsection



