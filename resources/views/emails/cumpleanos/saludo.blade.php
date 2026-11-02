<div style="text-align: center;"><img src="{{ $image }}" width="600" height="300" alt="Cumpleaños"></div>
<span style="font-family: Arial;">
@if (count($birthdays) > 1)
<div style="text-align: center;"><strong>Feliz cumpleaños {{ $person['nombre'] }}!!!</strong>
<br><br><strong>Este Tribunal, en nombre de todos sus funcionarios y funcionarias, te saluda muy afectuosamente y te desea un día lleno de alegría y amor junto a tus seres queridos.</strong>
<br><br></div>
<div style="text-align: center;"><br><br><strong>En este día adicionalmente están de cumpleaños nuestros siguientes compañeros:</strong><br></div><br><br>
@foreach ($others as $other)
<div style="text-align: center;"><strong>{{ $other['nombre'] }}</strong>, su mail es {{ $other['email'] }}<br></div>
@endforeach
<div style="text-align: center;"><br><br><a href="{{ route('cumpleanos.index') }}">Cumpleaños</a></div>
@else
<br><br><div style="text-align: center;"><strong>Feliz cumpleaños {{ $person['nombre'] }}!!!</strong></div>
<br><br><div style="text-align: center;">Este Tribunal, en nombre de todos sus funcionarios y funcionarias, te saluda muy afectuosamente y te desea un día lleno de alegría y amor junto a tus seres queridos.
<br><br><strong>Felicidades!!!!.</strong><br><br>
Revisa el listado completo de Cumpleañeros en el siguiente link:<br><br>
<a href="{{ route('cumpleanos.index') }}">Cumpleaños</a></div>
@endif
</span><br><br><div style="font-style: italic; text-align: center;">Habilita la vista de de la imagen para una correcta visualización</div>
