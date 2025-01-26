<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tablas OFICIO/LIBERTADES</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto|Varela+Round">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>
    <header class="banner">
        <h1 class="banner-title">4° Tribunal de Juicio Oral en lo Penal de Santiago</h1>
        <img src="/img/pjud_blanco.png" alt="Descripción de la imagen">
    </header>
    <div class="container mt-4">
    <h1 class="text-center mb-4">Listado de Oficios</h1>

    @if (!empty($data))

      @php
        $lastFiveData = array_slice($data, -5);
      @endphp 

        <div class="container-fluid">
            <table class="table-responsive table-striped-columns table-bordered">
                <thead class="thead align-top">
                    <tr>
                        <th scope="col">Número de Oficio</th>
                        <th scope="col">Año</th>
                        <th scope="col">Causa Asignada</th>
                        <th scope="col">Solicitante</th>
                        <th scope="col">Dirigido a</th>
                        <th scope="col">Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lastFiveData as $tabla)
                        <tr>
                            <td>{{ $tabla['Numentregado'] ?? 'N/A' }}</td>
                            <td>{{ $tabla['año'] ?? 'N/A' }}</td>
                            <td>{{ $tabla['CausaAsig'] ?? 'N/A' }}</td>
                            <td>{{ $tabla['UserSolicitante'] ?? 'N/A' }}</td>
                            <td>{{ $tabla['UserDirigido'] ?? 'N/A' }}</td>
                            <td>{{ \Carbon\Carbon::parse($tabla['created_at'])->format('d-m-Y H:i') ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="alert alert-warning text-center">
            No hay datos disponibles.
        </div>
    @endif
</div>


    <footer>
      <a href="{{ url('/tablas') }}" class="footer-button">Ultimos Oficios</a>
      <a href="{{ url('/SistOficioLibertades') }}" class="footer-button">Página Principal</a>
      <a href="{{ url('/tabla2') }}" class="footer-button">Ultimas Libertades</a>
    </footer>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
    <!--<script src="/js/app.js"></script>-->
</body>

</html>