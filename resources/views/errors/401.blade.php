@include('errors.page', [
    'code' => 401,
    'title' => 'Debes iniciar sesión para continuar.',
    'message' => 'Tu sesión no permite acceder a este recurso.',
    'showLogin' => true,
])
