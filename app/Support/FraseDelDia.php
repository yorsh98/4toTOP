<?php

namespace App\Support;

use Carbon\CarbonInterface;

final class FraseDelDia
{
    private const FRASES = [
        'Cada paso cuenta, incluso los más chicos.',
        'Hoy es un buen día para avanzar a tu ritmo.',
        'Una cosa a la vez, que así se llega lejos.',
        'Si hay dudas, preguntamos y seguimos.',
        'Entre todos siempre aparece una buena idea.',
        'La constancia hace su trabajo, día tras día.',
        'Un buen saludo puede cambiar la mañana.',
        'Cada aporte suma, por pequeño que parezca.',
        'Vamos paso a paso, sin perder el buen humor.',
        'Hoy también podemos aprender algo nuevo.',
        'Cuando compartimos la carga, avanzamos mejor.',
        'Una tarea terminada merece una sonrisa.',
        'El trabajo bien hecho empieza por escucharnos.',
        'Pedir ayuda también es trabajar en equipo.',
        'Lo que avanzaste hoy ya es un buen comienzo.',
        'Una buena conversación puede abrir caminos.',
        'El esfuerzo de cada día va dejando huella.',
        'Que no falte el ánimo ni las ganas de colaborar.',
        'Lo difícil se hace más llevadero en compañía.',
        'Una idea sencilla puede hacer la diferencia.',
        'Hoy toca avanzar, aunque sea de a poquito.',
        'Si algo no resulta, ajustamos y seguimos.',
        'Un descanso breve puede ordenar las ideas.',
        'Cada día trae una nueva oportunidad de aportar.',
        'La confianza se construye con pequeños gestos.',
        'Con calma también se llega a buen puerto.',
        'Una mano a tiempo hace la diferencia.',
        'Hagamos espacio para escuchar otras ideas.',
        'A veces avanzar es saber cuándo hacer una pausa.',
        'Con respeto, hasta los días pesados pasan mejor.',
        'Hoy puede salir algo bueno de una idea compartida.',
        'La paciencia es una gran compañera de equipo.',
        'Un paso firme vale más que correr sin rumbo.',
        'Gracias por ponerle empeño a lo cotidiano.',
        'Lo importante es seguir, incluso si vamos lento.',
        'Una buena pregunta puede destrabar la mañana.',
        'El ánimo crece cuando celebramos los avances.',
        'Siempre hay algo que podemos mejorar juntos.',
        'La colaboración hace más amable el camino.',
        'Un poco de orden deja espacio para buenas ideas.',
        'Cada desafío trae algo que podemos aprender.',
        'Hoy es un buen día para tender una mano.',
        'Las buenas ideas también nacen en las pausas.',
        'Avanzar juntos tiene su propia recompensa.',
        'El trabajo de cada persona sostiene al equipo.',
        'Escuchar con atención también es una forma de ayudar.',
        'Lo que hoy cuesta, mañana se vuelve costumbre.',
        'Un trabajo cuidadoso habla por sí solo.',
        'La amabilidad hace más fácil cualquier jornada.',
        'Cada problema resuelto deja una lección.',
        'Confía en lo que has aprendido en el camino.',
        'Un buen día se construye con pequeños logros.',
        'Trabajar con calma también es trabajar bien.',
        'Agradecer el esfuerzo ajeno fortalece al equipo.',
        'La claridad de hoy evita confusiones mañana.',
        'Lo simple bien hecho vale mucho.',
        'Seguir aprendiendo es parte del oficio.',
        'Con orden y paciencia, todo encuentra su lugar.',
        'El compromiso diario hace la diferencia.',
        'Terminar el día con algo resuelto ya es un logro.',
    ];

    public static function hoy(?CarbonInterface $fecha = null): string
    {
        $fecha ??= now('America/Santiago');

        return self::FRASES[$fecha->dayOfYear % count(self::FRASES)];
    }

    public static function todas(): array
    {
        return self::FRASES;
    }
}
