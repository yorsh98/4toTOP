@include('errors.page', [
    'code' => $exception->getStatusCode(),
    'title' => 'El sistema no pudo completar la solicitud.',
    'message' => 'Intenta nuevamente en unos minutos. Si el problema continúa, informa a la persona administradora.',
])
