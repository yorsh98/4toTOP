# Plan: paginación y firmas en la conversión PDF → Word

Fecha: 21 de octubre de 2026. Origen: `90-2023 final.pdf`, una unión de documentos hecha con iLovePDF. El PDF tiene 1.285 páginas y el Word generado ocupaba 2.488 hojas en LibreOffice.

## Diagnóstico medido

Las mediciones se hicieron renderizando el DOCX con LibreOffice 26.2. Para cada página del PDF se tomó una huella con sus primeras palabras y se buscó en el resultado, para contar cuántas hojas ocupa cada página original.

| # | Causa | Alcance | Evidencia |
|---|---|---|---|
| 1 | **Fuentes ausentes.** El PDF usa Garamond y Calibri. En Linux se sustituyen por Noto Serif y Noto Sans: sobre 169.000 caracteres reales del documento, Noto Serif es un **23,5 % más ancha** que Garamond | Todo el documento | Con fuentes sustitutas: 2.488 hojas. Con las fuentes extraídas del PDF: 1.581. Corregir solo el margen, sin las fuentes: 2.550 |
| 2 | **Margen inferior agrandado.** El script lo fijaba a la altura del QR (737 pt), pero el número de página está en 732–756 pt | Pp. 86–1.285 | Con fuentes correctas, las páginas desbordadas bajan de 299 a 42 al corregir el margen |
| 3 | **PDF unido con pies distintos.** El código de barras del primer documento aparece en 80 de 1.285 páginas; la regla «al menos la mitad de las páginas» no lo detecta y queda en el cuerpo | Pp. 1–80 | Desborde en cuanto el margen se ajusta al cuerpo |
| 4 | **Altura del pie reutilizada entre tamaños.** Al compartir el pie con la sección anterior (Carta), se reutilizaba su altura y las páginas Oficio quedaban con un margen inferior de 216 pt | Pp. 81–85 (612 × 1.008) | Las 5 páginas se desbordan |
| 5 | **Firmas aplanadas como tabla.** Las firmas electrónicas del final son dibujos (borde, franja azul, sello e ícono como imágenes y texto), no campos de formulario. `pdf2docx` las convierte en una tabla y pinta toda la celda con el color de la franja | P. 1.285 | Una firma queda tapada por un bloque azul y la otra pasa a la hoja siguiente |

## Fase 1: errores del script (causas 2, 3 y 4)

1. No agrandar nunca el margen inferior que calcula `pdf2docx`: usar el menor entre ese margen y el espacio libre bajo el pie.
2. Calcular el margen de cada sección con su propia altura de página.
3. Detectar pies y membretes por tramos: un elemento repetido en la misma posición de la franja superior o inferior en ≥ 3 páginas (o en todas, si el documento tiene menos) es pie o membrete de esas páginas.
4. Detectar números de página: un número entero en la misma franja y posición, que aumenta de uno en uno. Se llevan al pie como campo `PAGE`, con el número inicial de cada tramo.
5. Tests de regresión con PDF sintéticos: un documento unido con dos pies, páginas Carta y Oficio mezcladas, y números de página.

## Fase 2: firmas aplanadas (causa 5)

1. Detectar el recuadro de firma: un rectángulo con borde que contiene al menos una imagen y texto, con una franja rellena de color pegada a su borde izquierdo y de la misma altura.
2. Convertirlo en una imagen fiel de su región, como ya se hace con los campos de firma, para que `pdf2docx` no lo interprete como tabla.
3. Guardar el texto de la firma (nombre, cargo, tribunal, fecha) como texto alternativo de la imagen.
4. Validar con 90-2023, AA 401 y RIT 222, y con un test sintético.

## Fase 3: fuentes (causa 1)

1. Definir con qué se abren los Word. Microsoft Office en Windows trae Garamond y Calibri. Con LibreOffice en Linux, la paginación cambia siempre que falten.
2. En equipos Linux: Calibri tiene un equivalente de métricas compatibles, `fonts-crosextra-carlito`. Garamond no tiene equivalente libre del mismo ancho: hay que instalarla con licencia o abrir el documento en Word.
3. Avisar en el conversor cuando el PDF use fuentes que no están instaladas en el servidor.
4. Opcional: incrustar las fuentes completas en el DOCX, si el servidor las tiene y la licencia lo permite. Las fuentes incrustadas en el PDF son subconjuntos y no traen la tabla `OS/2` con el permiso de incrustación, así que no sirven para esto.

## Fase 4: verificación y casos restantes

1. Investigar las ~42 páginas de la sentencia que siguen desbordándose con fuentes correctas (por ejemplo, la 126 ocupa 3 hojas).
2. Convertir la medición por huellas en una herramienta de control de calidad para un conjunto de PDF representativos.
3. Prueba final en Microsoft Word en Windows con 90-2023, AA 401 y RIT 222.

## Resultado esperado

- Con las fases 1 y 2 y las fuentes correctas: alrededor de 1.285 hojas más unas 40 restantes (≈ 3 %), y firmas iguales al original.
- Sin las fuentes correctas, la paginación no se puede conservar: el texto es físicamente un 23 % más ancho.

## Estado

- [ ] Fase 1
- [ ] Fase 2
- [ ] Fase 3
- [ ] Fase 4
