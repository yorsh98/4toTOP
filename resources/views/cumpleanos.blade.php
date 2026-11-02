<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuestros Cumpleañeros</title>
    <link rel="icon" href="{{ asset('favicon.webp') }}" type="image/webp">
    <link href="{{ asset('css/cumpleanos.css') }}" rel="stylesheet">
</head>
<body>
<a class="back" href="{{ route('welcome') }}">‹ Atrás</a>
<main id="container">
    <div><img src="{{ asset('img/cumpleanos/cumpleanos.png') }}" id="Image1" alt="Cumpleaños"></div>
    <table id="Table1">
        <thead><tr><th scope="col">NOMBRE</th><th scope="col">FECHA</th></tr></thead>
        <tbody>
        @foreach ($people as $person)
            <tr @class(['today' => $person['hoy']])><td>{{ $person['nombre'] }}</td><td>{{ $person['fecha'] }}</td></tr>
        @endforeach
        </tbody>
    </table>
</main>
</body>
</html>
