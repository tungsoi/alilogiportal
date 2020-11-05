<!DOCTYPE html>
<html lang="{{ config('app.locale') }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="renderer" content="webkit">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ Admin::title() }} @if($header) | {{ $header }}@endif</title>
    <!-- Tell the browser to be responsive to screen width -->
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

    <link rel="shortcut icon" href="{{ asset('images/favicon.jpeg') }}">

    {!! Admin::css() !!}

    <script src="{{ Admin::jQuery() }}"></script>
    {!! Admin::headerJs() !!}
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <style>
        body {
            font-family: "Tahoma" !important;
            font-size: 12px !important;
        }
        
        h1, h2, h3, h4, h5, h6, .h1, .h2, .h3, .h4, .h5, .h6 {
            font-family: "Tahoma" !important;
        }
        tfoot {
            display: table-row-group;
        }
        th {
            padding: 10px 10px !important;
            font-weight: 600;
            font-size: 12px !important;
        }

        .grid-row-view, .grid-row-edit, .grid-row-deposite {
            margin-top: 5px;
        }

        .editable-click, a.editable-click, a.editable-click:hover {
            border-bottom: none;
        }

        form .box-footer .pull-right {
            float: left !important;
            margin-left: 20px;
        }

        #purchase_total_items_price_new {
            text-align: right !important;
        }
    </style>

</head>

<body class="hold-transition {{config('admin.skin')}} {{join(' ', config('admin.layout'))}}">

@if($alert = config('admin.top_alert'))
    <div style="text-align: center;padding: 5px;font-size: 12px;background-color: #ffffd5;color: #ff0000;">
        {!! $alert !!}
    </div>
@endif

<div class="wrapper">

    @include('admin::partials.header')

    @include('admin::partials.sidebar')

    <div class="content-wrapper" id="pjax-container">
        {!! Admin::style() !!}
        <div id="app">
        @yield('content')
        </div>
        {!! Admin::script() !!}
        {!! Admin::html() !!}
    </div>

    @include('admin::partials.footer')

</div>

<button id="totop" title="Go to top" style="display: none;"><i class="fa fa-chevron-up"></i></button>

<script>
    function LA() {}
    LA.token = "{{ csrf_token() }}";
    LA.user = @json($_user_);
</script>

<!-- REQUIRED JS SCRIPTS -->
{!! Admin::js() !!}


<script>
    $(document).ready(function () {
        $(document).on('change','select[name="purchase_service_fee_percent"]', function () {
            let value = $(this).val();

            $.ajax({
                type: 'GET',
                url: '/api/service_percent',
                dataType: 'json',
                data: {
                    'q' : value,
                    'id': $('.id').val()
                },
                success: function(data) {
                    $('input[name="purchase_order_service_fee"]').val(data);
                }
            });
        });
    });
</script>


</body>
</html>
