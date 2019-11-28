<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>意见反馈</title>
    <style>
        body {
            margin: 0;
            background: #f5f5f8;
            color: #757575;
        }

        .content {
            max-width: 400px;
            margin: 0 auto;
            min-height: 100vh;
        }
        .bg-white {
            background: #fff;
            padding: 1rem;
        }
    </style>
    @yield('style')
</head>
<body>
@yield('content')
@yield('script')
</body>
</html>