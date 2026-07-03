<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Cetak') - {{ brand_name() }}</title>
    <link rel="stylesheet" href="{{ asset('css/invoice-print.css') }}">
    @stack('css')
</head>
<body class="print-body">
    @yield('content')
    @stack('js')
</body>
</html>
