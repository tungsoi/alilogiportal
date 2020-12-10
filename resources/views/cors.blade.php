<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="renderer" content="webkit">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Demo Cross Origin Resource Sharing</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

    <link rel="shortcut icon" href="{{ asset('images/favicon.jpeg') }}">

    {!! Admin::css() !!}

    <script src="{{ Admin::jQuery() }}"></script>
    {!! Admin::headerJs() !!}
    

</head>

<body class="hold-transition">

@if($alert = config('admin.top_alert'))
    <div style="text-align: center;padding: 5px;font-size: 12px;background-color: #ffffd5;color: #ff0000;">
        {!! $alert !!}
    </div>
@endif

<div class="wrapper">
    <center>
        <h1>Demo Cross Origin Resource Sharing</h1> <br>

        <button class="btn btn-lg btn-success btn-cors">Gọi dữ liệu sang domain https://alilogi.vn</button>
    </center>
</div>

<!-- REQUIRED JS SCRIPTS -->
{!! Admin::js() !!}
<script>
    $('.btn-cors').click(function() {
        $.ajax({
            url: "https://alilogi.vn/getOrders",
            type: 'POST',
            dataType: 'json',
            data: {
                'API_TOKEN': 'testdemocors123@@'
            },
            xhrFields: {
                withCredentials: true
            },
        }).done(function(response) {
            console.log(response);
        }).fail(function(err) {
            console.log(err);
        });
    });
</script>
</body>
</html>
