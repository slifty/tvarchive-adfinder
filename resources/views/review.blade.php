
@extends('layouts.app')

@section('head')

<script type="text/javascript">
    var media_id = {{ $media_id }};
    var token = "{{ csrf_token() }}";
    $(function() {
        $("#isDistractor").click(function() {
            // Mark this as a distractor
            $.ajax({
                method: "POST",
                data: {
                    _token: token
                },
                url: "{{url('/')}}/api/register_distractor/" + media_id
            })
            .done(function(d) {
                $("body").text("You can close the window now.");
                window.close();
            })

            // Close the window
            $("body").text("Logging your decision, don't close this window until it says to!")

        });
        $("#isTarget").click(function() {
            window.location = "{{url('/')}}/canonical/" + media_id;
        });
    });
</script>

@endsection

@section('content')

<iframe width="640" height="480" frameborder="0" allowfullscreen src="https://archive.org/embed/{{ $archive_id }}?start={{ $start }}&end={{ $end }}" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>

<div id="isDistractor">Throw Away</div>

<div id="isTarget">Political Ad</div>

@endsection



