@include('errors.page', [
    'code' => $exception->getStatusCode(),
    'title' => 'No fue posible procesar la solicitud.',
    'message' => 'Revisa la información enviada o vuelve al inicio para continuar.',
])
