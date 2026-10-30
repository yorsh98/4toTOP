<div style="text-align: center;"><img src="{{ $image }}" width="600" height="300" alt="Cumpleaños"></div>
<div style="text-align: center;"><span style="font-family: Arial;"><br><br>
<strong>Estimado(a) funcionario(a)</strong><br><br>
Le recordamos a Usted que el día de hoy<br><br><strong>{{ $date->format('d-m-Y') }}<br><br></strong>
@if (count($birthdays) === 1)
Se encuentra de cumpleaños nuestr@ querid@, compañero(a)<br><br><strong>{{ $birthdays[0]['nombre'] }}</strong><br><br>
Para que no se olvide de saludarlo en este dia tan especial. Su email es<br><br>{{ $birthdays[0]['email'] }}
@else
Se encuentran de cumpleaños nuestr@s querid@s compañeros:<br><br>
@foreach ($birthdays as $person)<strong>{{ $person['nombre'] }}</strong>, su mail es {{ $person['email'] }}<br>@endforeach
<br><br>Para que no se olvide de saludarlo en este dia tan especial.
<br><br>Saluda Atte. el 4º Tribunal de Juicio Oral en lo Penal de Santiago.
<br><br>Revisa el listado completo de Cumpleañeros en el siguiente link: <a href="{{ route('cumpleanos.index') }}">Cumpleaños</a>
@endif
@if (count($upcoming))
<br><br><strong>Próximos cumpleaños</strong><br><br>
@foreach ($upcoming as $person){{ $person['nombre'] }} — {{ $person['fecha'] }}<br>@endforeach
@endif
</span></div>
