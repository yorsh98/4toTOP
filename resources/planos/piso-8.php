<?php

[$horizontal, $vertical] = require __DIR__.'/piso-7.php';

// Recinto TTyPP al oeste y dos divisiones desplazadas en las oficinas del sur.
$horizontal[307][] = [21, 61];
$horizontal[458][] = [21, 61];
$vertical[61] = [[307, 458]];
unset($vertical[215], $vertical[279]);
$vertical[216] = [[496, 610]];
$vertical[283][] = [496, 610];

return [$horizontal, $vertical];
