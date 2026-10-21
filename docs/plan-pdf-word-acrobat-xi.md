# Plan completo: PDF a Word con Acrobat XI como referencia obligatoria

Fecha: 21 de octubre de 2026. Estado: **G0 (licencia de Acrobat XI) sin resolver; vía A no iniciada.**

> **Implementado el 21/10/2026, a pedido del propietario**: se busca una salida equivalente a la de Acrobat XI, no su procedencia (vía B de §5.3, con un motor local). Incluye el motor `pdf2docx` con encabezado y pie al estilo XI, la mejora provisional de LibreOffice de §9, la detección por página de escaneados y mixtos, el OCR opcional, la trazabilidad de motor y hashes, y las correcciones de paginación y firmas de [plan-paginacion-firmas-pdf-word.md](plan-paginacion-firmas-pdf-word.md). Detalle, mediciones y límites: [file-conversions.md](file-conversions.md). Siguen pendientes el corpus y la regresión de §8, la prueba en Microsoft Word (G4/G5) y la revisión de la licencia AGPL de PyMuPDF. El resultado no se etiqueta como salida de Adobe.

## 1. Decisión de producto

El resultado requerido debe satisfacer **simultáneamente** estos dos objetivos:

1. **Fidelidad visual:** conservar, respecto de la salida de referencia de Adobe Acrobat XI, presentación, geometría, contenido visible, imágenes, tablas, encabezados, pies y paginación.
2. **Reconstrucción editable:** conservar una estructura de Word comparable a la referencia: párrafos, estilos, listas, tablas y objetos correctamente relacionados, no sustituir cada página por una imagen ni conformarse con cuadros de texto por línea.

No son dos modos alternativos entre los que se acepta cumplir solo uno. Un documento parecido pero no editable, o editable con contenido desplazado u oculto, **no cumple**.

La corrección provisional de LibreOffice es útil para los fallos conocidos, pero **no se presentará como el motor de reconstrucción de Adobe ni como cumplimiento final de este requisito**.

### 1.1 Qué significa «tal cual Adobe»

Se deben distinguir tres condiciones:

| Condición | Exigencia |
|---|---|
| Procedencia Adobe | Si el archivo debe ser literalmente una salida de Acrobat XI, debe generarse con esa aplicación/motor en una modalidad autorizada. Otro motor no se etiqueta como Adobe por producir un resultado parecido. |
| Equivalencia funcional | Apariencia y comportamiento editable deben compararse con una referencia producida con versión, configuración y entorno conocidos. No basta verificar texto o número de páginas. |
| Identidad binaria | No se presupone que dos exportaciones independientes tengan el mismo SHA-256: fechas, propiedades, IDs y empaquetado pueden variar. Si se exige identidad binaria entre ejecuciones, debe declararse como requisito adicional y demostrarse; este plan no la promete. |

Para la entrega de una conversión concreta sí se comprobará que el DOCX descargado conserve los mismos bytes que produjo el motor seleccionado. No se abrirá y volverá a guardar con LibreOffice una salida Adobe para «normalizarla».

## 2. Condición previa: licencia y modalidad de ejecución

**Bloqueo inicial:** la documentación oficial del SDK indica que utilizar productos Acrobat como proceso servidor accesible por múltiples clientes, en general, no está diseñado, probado ni licenciado para ese uso, salvo autorización explícita en la documentación aplicable.

Por tanto:

- Tener una licencia de escritorio o el instalador no demuestra autorización para ofrecer PDF→Word como servicio web.
- Una API COM/IAC o una función JavaScript disponible no concede por sí misma derechos de explotación como servidor.
- No se desplegará Acrobat XI desatendido para atender usuarios web mientras no exista una habilitación contractual y técnica explícita para el caso de uso.
- No se resolverá este bloqueo extrayendo DLL, parcheando licencias, recuperando claves internas, copiando credenciales de inicialización ni evitando controles de seguridad.
- Una máquina virtual, Wine o una sesión gráfica persistente no convierten un uso no autorizado en uno autorizado.

### Puerta de decisión G0

Antes de escribir el conector de producción, obtener y archivar fuera del repositorio público:

1. Identificación del producto, versión, licencia y titular.
2. Condiciones aplicables al uso multiusuario, automatización, concurrencia y servidor.
3. Confirmación de soporte técnico para el sistema operativo y la modalidad pretendida.
4. Revisión de riesgos del motor antiguo y aprobación del responsable de seguridad.
5. Decisión documentada sobre tratamiento de documentos judiciales y ubicación de los datos.

**Responsables:** propietario del sistema, administración/licenciamiento y seguridad. **Salida:** autorización verificable o decisión de no automatizar esa modalidad.

### Si G0 no se supera

Opciones válidas, sin redefinir silenciosamente el objetivo:

- Mantener la exportación en el escritorio autorizado de un operador, sujeta a sus condiciones de uso, y recibir en la web el DOCX que este haya generado. El diseño de ese flujo será explícito y no un servidor de escritorio encubierto.
- Consultar la disponibilidad de un motor Solid/Adobe con licencia y soporte para servidor. La disponibilidad comercial de la versión exacta identificada **no se ha verificado**; no se presupone que pueda adquirirse o redistribuirse.
- Evaluar otro motor servidor mediante pruebas, **solo si el propietario acepta cambiar el requisito de procedencia**. Un resultado equivalente no se llamará salida de Acrobat XI.
- Si la procedencia XI es innegociable y no hay modalidad autorizada para automatizarla, declarar bloqueada la automatización final. No publicar LibreOffice como sustituto indistinguible.

Adobe PDF Services, Acrobat moderno, PDF Library y un producto Solid actual **no se consideran automáticamente el mismo motor ni el mismo resultado que Acrobat XI**.

## 3. Evidencia ya obtenida

### 3.1 Motor y componentes identificados

La inspección del instalador original con REA 6.7.0 y Ghidra 12.1 identifica esta arquitectura estática:

```text
Acrobat XI / selección de exportación Word
  com.adobe.acrobat.docx
           │
     SaveAsRTF.api                 Complemento Adobe y traducción de opciones
           │ carga dinámica
     SCPdfBridge.dll               Documento Acrobat, HFT y adaptación OCR
           │ PdfConverterConvertEx
     ConverterCoreLight.dll        Coordinación, opciones y conversión
           │
     SolidCore.dll + dbCore.dll    Modelo documental intermedio
       ┌───┴──────────────────────┐
   PdfFlt.flt                 DocXFlt.flt / RtfFlt.flt
   Entrada/reconstrucción     Escritura de Word sobre el modelo
       │
   PDFLibTool.dll / ImageTool.dll / SPal.dll / componentes OCR
```

Versiones declaradas por los recursos de los binarios inspeccionados:

| Componente | Versión |
|---|---|
| `SaveAsRTF.api` | 11.0.0.379 |
| `SCPdfBridge.dll` | 7.2.2517.0 |
| `ConverterCoreLight.dll` | 7.2.2517.0 |
| `SolidCore.dll` | 7.2.2517.0 |
| `PdfFlt.flt` | 7.2.2517.0 |

Los módulos examinados son Windows x86. El puente consume tablas de funciones del host Acrobat (`AcroSupport`, `PDModel`, `PDFEditWrite`, `PDFEditRead`, entre otras). Encontrar exports como `SolidConvert` y `PdfConverterConvertEx` **no demuestra una API pública autónoma admitida para servidores**.

Se comprobaron rutas de reconstrucción/configuración y escritores con tamaños de página, márgenes, columnas, tablas de rejilla fija, combinaciones, anclajes, geometría, estilos y espaciado. La ruta de imágenes ordinarias seguida escribe VML. No se recuperó el código fuente original ni la totalidad de las heurísticas de reconstrucción.

El análisis contiene 106 registros REA únicos, incluidos 61 procedimientos seleccionados descompilados; no es una descompilación exhaustiva del producto. No se ejecutó Acrobat en esa investigación.

### 3.2 Comparación de los tres archivos aportados

Sobre `AA 401-2026.pdf`, la salida web y la salida atribuida por el usuario a la aplicación:

- PDF de cinco páginas Letter y texto extraíble.
- Salida web declarada como LibreOffice 26.2.6.3.
- Reproducción local mediante `writer_pdf_import` y `Office Open XML Text` con las mismas 1.306 palabras, en el mismo orden y con diferencia máxima de coordenadas de 0,0 puntos respecto del render de la salida web suministrada.
- Desplazamiento del cuerpo de aproximadamente 28,7 puntos hacia abajo y aproximadamente 28 puntos a la derecha: anclajes columna/párrafo reciben además el origen de márgenes de 567 twips = 28,35 puntos.
- Fondos blancos con orden de pintura incorrecto que ocultan contenido en la página 5, aunque el texto sigue siendo extraíble.
- Una copia diagnóstica con referencias a página y fondos detrás reduce la mediana del desplazamiento Y del subconjunto medido a aproximadamente 0,38 puntos y restaura contenido visible. No garantiza el comportamiento de otros documentos o lectores.
- La salida web contiene 182 cuadros de texto y 12 imágenes en la rama activa DrawingML: mejorar posiciones no los convierte en párrafos reconstruidos.

**Precaución de referencia:** el DOCX atribuido a la aplicación contiene metadatos de Microsoft Office Outlook. Su procedencia se acepta como información del usuario, pero no se certifica que sea una exportación directa e inalterada de XI. Hay que generar referencias nuevas bajo control antes de declarar paridad final.

### 3.3 Ubicación de las evidencias existentes

Fuera de este repositorio, en el equipo donde se hizo el análisis:

```text
/home/yorsh/Descargas/OneDrive_2026-10-21/Adobe Acrobat XI/analisis-pdf-word/
  INFORME.md
  DOCX-DETALLE.md
  OCR-DEPENDENCIAS.md
  indice-rea.json
  componentes-sha256.json
  rea-*-evidencias.json
  comparacion-AA401/COMPARACION.md
  comparacion-AA401/validacion.json
  comparacion-AA401/metricas-comparacion.json
```

No se incorporarán al Git público binarios propietarios, claves, licencias, PDFs judiciales sin anonimizar ni documentos con datos personales. Las métricas anteriores son evidencia de esta muestra y entorno, no una certificación universal.

## 4. Estado de la aplicación

Flujo actual compartido por frontoffice y backoffice:

```text
ConvertsFiles
  → FileConversionService::create()
  → ConvertFile::handle()
  → ConvertFile::convertPdf()
  → LibreOffice
  → validación de ZIP/XML y presencia de texto
  → result.docx / estado ready
  → DownloadConversionController
```

| Archivo | Papel / intervención prevista |
|---|---|
| `app/Livewire/Concerns/ConvertsFiles.php` | Validación de subida y entrada común. Mantener autorización, límites y separación de áreas. |
| `app/Services/FileConversionService.php` | Almacenamiento, cuota, registro y despacho. Conservar este punto de entrada. |
| `app/Jobs/ConvertFile.php` | Punto único para seleccionar motor, ejecutar, validar y publicar. El cambio debe cubrir ambas interfaces sin duplicarse. |
| `config/conversions.php` | Configuración del motor y límites cuando se apruebe la modalidad. Hoy solo hay ejecutables de LibreOffice/FFmpeg y límites básicos. |
| `app/Models/FileConversion.php` y migraciones | Identidad, estado y trazabilidad. Ampliar metadatos solo cuando se integre el motor aprobado. |
| `app/Http/Controllers/DownloadConversionController.php` | Descargas autorizadas. No mezclar conversión con el controlador de descarga. |
| `app/Console/Commands/CleanFileConversions.php` | Limpieza actual; ampliar a artefactos externos y ejecuciones incompletas cuando existan. |
| `resources/views/livewire/file-converter.blade.php` | Identificar el resultado y sus límites sin prometer conservación criptográfica de firmas ni paridad no validada. |
| `tests/Feature/FileConversionTest.php` | Prueba básica existente: texto y negrita. Insuficiente para certificar apariencia o reconstrucción editable. |
| `docs/file-conversions.md` | Documentación operativa a actualizar al desplegar, sin describir fases futuras como implementadas. |

Reutilizar cola, almacenamiento privado, perfiles aislados, permisos, cuotas y descarga. No rehacer la UI o el módulo de audio para resolver PDF→Word.

## 5. Arquitectura objetivo, condicionada a G0

### 5.1 Vía A: Acrobat XI real, solo con modalidad autorizada

```text
Laravel: solicitud validada / archivo privado / ID de conversión
       │
       └── trabajo de conversión y referencia privada al PDF
                         │
          ejecutor autorizado en entorno Windows controlado
            Acrobat XI + plugins instalados normalmente
            misma versión/configuración/fuentes de la referencia
                         │
                  DOCX producido por Adobe
                         │
          comprobación, hash y publicación sin reescritura
                         │
                descarga autorizada en Laravel
```

Esta arquitectura es una **posibilidad técnica a validar**, no una afirmación de soporte o licenciamiento. El transporte y ejecutor solo se diseñan en detalle después de G0 y de una prueba técnica local. No crear por anticipado un microservicio, endpoint público o framework de proveedores.

La aplicación y sus componentes se instalarán en el entorno autorizado; no se tratarán DLL extraídas como si fueran un SDK autónomo. Las diferencias entre aplicación x86 y sistema operativo/cliente deben probarse, sin exigir por deducción un Windows de 32 bits.

### 5.2 Interfaz de automatización a investigar

La documentación SDK describe IAC/OLE/DDE en Windows y el identificador `com.adobe.acrobat.docx` para `Doc.saveAs`. Esto da una **línea de investigación**, no una integración ya verificada en XI:

1. Comprobar interfaces realmente presentes en la instalación y versión elegidas.
2. Enumerar los conversores disponibles y verificar el de DOCX.
3. Verificar contexto permitido, permisos, rutas seguras y tratamiento de errores/cancelaciones.
4. Reproducir mediante interfaz admitida el resultado de la exportación manual de referencia.
5. Comprobar que no haya cuadros de diálogo inesperados, documentos retenidos o procesos huérfanos.
6. No desactivar globalmente las protecciones de Acrobat para hacer funcionar la prueba.

Si el método documentado no permite reproducir la configuración de XI o la modalidad no está soportada/autorizada, no forzar la integración mediante APIs internas ni automatización de clics como solución silenciosa de producción. Registrar el bloqueo y volver a la decisión de modalidad.

### 5.3 Vía B: motor de reconstrucción autorizado para servidor

Solo procede con aprobación explícita del propietario si no puede utilizarse la vía A y se acepta equivalencia en lugar de procedencia XI. Evaluar motor, versión, configuración, privacidad y derechos de servidor con el corpus de referencia.

Un motor con marca Adobe o Solid no supera automáticamente las pruebas. No anunciar «mismo motor XI» sin evidencia y derechos verificables. Si falla cualquiera de los dos objetivos, no cumple la alternativa aprobada.

### 5.4 Vía manual cuando la procedencia XI es obligatoria

Si la automatización no es viable pero existe un uso de escritorio autorizado, diseñar una intervención de operador claramente identificada: entrada controlada, exportación en XI, asociación del DOCX al trabajo y revisión de procedencia. La web no debe simular que una exportación de otro motor procede de ese operador.

Los estados nuevos y permisos de esa intervención solo se añadirán si se selecciona esta modalidad. No implementar a la vez las tres vías.

## 6. Plan por fases y puertas de aceptación

### Fase 0 — Licencia, entorno y referencias confiables

Tareas:

- Completar G0 y escoger una sola vía de implementación.
- Identificar versión exacta de XI, parches, plugins, Windows, fuentes y configuración de exportación/OCR.
- Exportar manualmente muestras nuevas y registrar PDF de origen, DOCX, ajustes, hashes, operador y fecha.
- Definir qué configuración de XI es la referencia para cumplir ambos objetivos; no comparar muestras generadas con modos distintos como si fueran iguales.
- Identificar el lector objetivo: versión de Word de escritorio y, si son necesarios, Word Online u otros lectores como matrices adicionales separadas.

Aceptación G1: modalidad permitida y referencias reproducibles y trazables. Si no existe autorización de servidor, solo continuar con las tareas compatibles con la modalidad permitida.

### Fase 1 — Prueba técnica del motor elegido

Antes de conectarlo a Laravel:

- Convertir el mismo PDF en el entorno autorizado mediante el camino manual y el camino propuesto.
- Comparar apariencia y estructura editable; no basta un retorno de éxito de la API.
- Probar error, PDF protegido, archivo dañado, cancelación y tiempo excedido.
- Verificar limpieza de documentos/procesos y ausencia de transferencia no autorizada de datos.
- Medir duración y consumo con documentos representativos.
- Empezar secuencialmente; no asumir que una instancia de Acrobat es multihilo o admite varias conversiones simultáneas. Cualquier concurrencia posterior requiere validación y licencia.

Aceptación G2: salida comparable a la referencia en ambos objetivos, dentro de la modalidad admitida. Si la prueba no puede generar el mismo resultado de XI, no continuar afirmando que lo hará una integración web.

### Fase 2 — Integración mínima con Laravel

Tareas previstas:

1. Mantener `FileConversionService` como punto de entrada y `ConvertFile` como coordinador.
2. Separar únicamente la ejecución PDF del resto del trabajo cuando se integre el motor real; no crear una jerarquía de proveedores antes de necesitarla.
3. Añadir la configuración del motor elegido; conservar audio y FFmpeg sin cambios funcionales.
4. Mantener archivo original privado e inmutable y usar salida provisional por intento.
5. Validar ZIP, partes necesarias, relaciones internas, XML con acceso externo deshabilitado y límites de tamaño/descompresión.
6. Aplicar comprobaciones de contenido/estructura y, cuando proceda, verificación visual de revisión. La mera presencia de `w:t` no equivale a calidad.
7. Registrar motor, versión observada, configuración, hashes de entrada/salida y resultado de validaciones. No registrar texto completo ni secretos en logs.
8. Publicar el archivo y marcar `ready` solamente cuando haya terminado la validación requerida.
9. Entregar el DOCX de Adobe sin pasarlo por normalización ni guardado con otro editor; el render de QA se hace sobre una copia.
10. Si falla Adobe, informar del fallo. **No hacer fallback oculto a LibreOffice** ni devolver un DOCX distinto como si proviniera de Adobe.

Si se necesita un ejecutor en otro equipo después de G0:

- Comunicación autenticada y restringida a la red interna o canal autorizado; no exponer COM ni un servicio sin autenticación.
- No aceptar rutas arbitrarias del cliente, comandos, JavaScript o URLs que el ejecutor deba descargar.
- Transferencias vinculadas al ID del trabajo, verificadas por hash y con retención limitada.
- Idempotencia por conversión/intento, control de exclusión y devolución autenticada del resultado.
- Recuperación de ejecutor caído, sin doble publicación ni sobrescritura de un resultado aprobado.

Esquema/archivos previstos, solo cuando esa modalidad lo requiera:

- `app/Jobs/ConvertFile.php`: coordinación común y ausencia de fallback silencioso.
- Un servicio concreto de exportación para el motor elegido; lenguaje/API del ejecutor por decidir tras G2.
- `config/conversions.php`: selección/configuración del motor aprobado y tiempos.
- Una migración adicional para metadatos de motor y hashes si se requiere persistirlos. No reescribir migraciones históricas para cambiar tablas ya desplegadas.
- `FileConversion`, registro y vista: mostrar motor/versión o estado de revisión cuando haya datos reales que mostrar.

Aceptación G3: frontoffice y backoffice usan el mismo recorrido, descargas autorizadas, resultado íntegro, fallos explícitos y prueba de que el resultado publicado corresponde a la salida recibida del motor.

### Fase 3 — Calidad y reconstrucción editable

Implementar la matriz de la sección 7 y ejecutarla sobre todas las muestras, no solo el caso `AA 401-2026`.

Para OCR:

- Reconocer por página cuando corresponda y preservar la geometría del resultado.
- Distinguir PDF con texto, escaneado y mixto. Un encabezado seleccionable no demuestra que todas las páginas tengan texto editable.
- Registrar idioma y configuración de OCR de la referencia.
- Si no existe soporte en la vía elegida, rechazar o declarar la limitación; no afirmar paridad para escaneados.

Aceptación G4: ambos objetivos aprobados para cada categoría declarada compatible. Los casos sin soporte deben identificarse, no contarse como conversiones equivalentes exitosas.

### Fase 4 — Operación, despliegue y aceptación de usuario

- Ejecutar conversiones con permisos mínimos y fuera del proceso HTTP.
- Para XI antiguo, entorno fuertemente aislado y uso aprobado: no abrir PDFs no confiables en el escritorio personal del operador ni exponer su perfil/red corporativa innecesariamente.
- Controlar CPU, RAM, espacio temporal, páginas/complejidad y tiempo; el tamaño en MB no es suficiente.
- Decidir una cola/ejecutor dedicado si las conversiones largas interfieren con notificaciones y otros trabajos del sistema. No aumentar concurrencia sin medición.
- Ajustar tiempos de proceso/trabajo y `retry_after` al driver realmente desplegado. Hoy son 300 s / 360 s / 420 s para el recorrido documentado con cola de base de datos; otros drivers tienen valores distintos.
- Limpieza en éxito y fallo de perfiles, temporales y copias del ejecutor; conciliación de trabajos huérfanos. No eliminar expedientes/entradas originales fuera de la retención autorizada.
- Mantener trazabilidad sin conservar indefinidamente documentos o capturas de QA.
- Actualizar instrucciones operativas y reiniciar workers al desplegar.
- Obtener aceptación en el Word y equipos reales de los usuarios.

Aceptación G5: validación técnica, de seguridad/licencia y del usuario; procedimiento de recuperación y reversión probado. Si se deshabilita Adobe por fallo, se suspende esa capacidad o se ofrece otra modalidad identificada, sin simular procedencia Adobe.

## 7. Matriz obligatoria de aceptación

### Objetivo A — Fidelidad visual

| Área | Criterio |
|---|---|
| Páginas | Tamaño, orientación, orden, número y rotaciones coherentes con la referencia. |
| Contenido | Nada perdido, oculto, duplicado, recortado o cambiado; los textos jurídicamente relevantes no admiten sustituciones silenciosas. |
| Texto | Posición, saltos, alineación, espaciados, escala, fuentes y métricas comparables a la referencia. |
| Tablas | Anchos, alturas, combinaciones, bordes, celdas y contenido visibles y alineados. |
| Imágenes/formas | Posición, escala, proporción, calidad, anclajes, wrapping y orden de capas correctos. |
| Encabezados/pies | Ubicación y repetición correctas; QR/códigos legibles, sin afirmar por ello autenticidad. |
| Formularios/apariencias | Tratamiento explícito, sin perder contenido visual de campos/anotaciones que la referencia conserva. |

Comparar referencia y candidato renderizados con el **mismo Word, fuentes y configuración**. El PDF original es otra referencia visual útil, pero no sustituye el DOCX de XI cuando el requisito es reproducir su salida. LibreOffice puede servir de control adicional, no de única autoridad de cómo renderiza Word.

Las tolerancias se fijan antes de la aprobación del piloto. Como propuesta inicial de trabajo para texto ordinario: mediana de desviación de origen ≤0,5 pt y percentil 95 ≤1,5 pt en un subconjunto definido. No son umbrales ya aprobados ni sustituyen la inspección visual, los máximos locales o la exactitud del contenido. Una firma tapada falla aunque la mediana sea buena.

### Objetivo B — Reconstrucción editable

| Área | Criterio |
|---|---|
| Párrafos | Agrupación comparable a XI; edición de una frase no obliga a mover cuadros de cada línea. |
| Tablas | Tablas/celdas reales cuando la referencia las reconstruye; no simulación con espacios o imágenes. |
| Listas/estilos | Niveles, sangrías, numeración y propiedades mantenidos conforme al documento de referencia. |
| Imágenes | Objetos independientes y anclajes apropiados; no rasterizar toda la página como atajo. |
| Orden de lectura | Selección/copiado y recorrido coherentes, comprobados además de apariencia. |
| Edición | Insertar/eliminar texto en un párrafo y editar una celda no provoca roturas ajenas a las que presenta la referencia. |
| OCR | Páginas escaneadas compatibles con texto editable y geometría revisada; casos mixtos no pasan por tener una sola palabra. |

No imponer a XI una estructura que él mismo no produce en un caso concreto; el criterio es comportamiento comparable a su salida controlada. No se garantiza reconstrucción perfecta de cualquier PDF.

### Integridad y procedencia

- Hash del PDF recibido y del DOCX producido, asociado a la conversión y al motor/versiones.
- Archivo publicado idéntico al producido para ese intento, sin reescritura intermedia.
- Si se usa otro motor por decisión explícita, identificarlo. Prohibido registrar «Adobe» por semejanza visual.
- Preservar el PDF original. La conversión a Word no conserva automáticamente la validez criptográfica de una firma del PDF. No se certifica una firma por mostrar su imagen o un QR.

## 8. Corpus de pruebas y protección de datos

Corpus mínimo de categorías:

- Texto simple y documentos largos de varias páginas.
- Encabezados/pies, imágenes, códigos QR y fondos superpuestos.
- Tablas con combinaciones, distintas alturas, bordes y múltiples páginas.
- Columnas, listas, estilos, pies de página y cambios de sección.
- Letter, A4, páginas horizontales, rotadas y tamaños mezclados.
- Fuentes incrustadas, fuentes ausentes y caracteres españoles.
- Escaneados, PDFs mixtos, formularios y anotaciones con apariencia.
- PDF protegido, dañado y casos de límite de recursos.

Guardar muestras reales y salidas Adobe en un repositorio privado de pruebas o almacenamiento de acceso restringido. En Git ordinario usar fixtures sintéticos/anonimizados, sin reintroducir personas o causas reales mediante imágenes, metadatos, texto alternativo, comentarios o archivos incrustados.

Pruebas previstas:

1. Unitarias de normalización provisional, si se entrega esa fase: geometría, representaciones alternativas, invariancia de texto/media y aplicación idempotente.
2. Integración del motor: éxito, error, tiempo excedido, archivo inválido, salida sin contenido, procedencia y publicación atómica.
3. Pruebas de API/cola/descarga: mismo comportamiento en ambas áreas, permisos, cuotas, expiración y ausencia de fallback oculto.
4. Regresión visual con parámetros y lector fijados; comparar también contenido y estructura, no solo píxeles.
5. Revisión de edición en Word y de escenarios OCR.
6. Fallos operativos: caída del ejecutor, resultado repetido, pérdida de conexión y limpieza.

Reutilizar Pest y las herramientas existentes. La suite Feature usa `RefreshDatabase` sobre MySQL de pruebas: preparar explícitamente ese entorno; no ejecutar migraciones/tests contra una base de producción. Mantener las modificaciones preexistentes del usuario en pruebas/migraciones.

## 9. Mejora provisional de LibreOffice: alcance separado

Puede realizarse mientras se resuelven las puertas de licencia/entorno, sin cambiar el criterio de aceptación final:

- Un normalizador con `ZipArchive`/`DOMDocument`/`DOMXPath` para patrones comprobados de esta salida.
- Referencias de página coherentes, conservando márgenes y ambas ramas alternativas cuando corresponda.
- Normalización conservadora de capas; no borrar fondos blancos ni generalizar los valores negativos de `z-index` del experimento.
- Integración antes de publicar `result.docx` en el recorrido actual.
- Pruebas de texto/media intactos, coordenadas, máscaras/superposiciones y documentos que no deben modificarse.

No añadir dependencias o un framework de motores para este ajuste. No aplicarlo a la salida Adobe. No anunciarlo como reconstrucción semántica ni como reemplazo de Solid. Las correcciones conocidas ayudan al objetivo visual de la muestra, pero por sí solas **no superan el objetivo editable**.

## 10. Riesgos y decisiones pendientes

| Riesgo / pregunta | Tratamiento |
|---|---|
| Acrobat XI de escritorio no habilitado para servicio web | Bloqueo G0. No desplegar como servidor sin autorización explícita. |
| Motor antiguo y PDFs no confiables | Aislamiento, política de seguridad y aceptación de riesgo; si no se pueden controlar, no operar esa ruta. |
| Referencia no generada bajo control | Nuevas exportaciones con instalación y ajustes conocidos. |
| API presente pero exportación no equivalente | Piloto previo a integración; no usar interfaces privadas como solución de producción improvisada. |
| Salida diferente por fuentes o lector | Fijar entorno y separar matrices de Word/otros lectores. |
| Párrafos aparentes son cuadros por línea | Pruebas estructurales y de edición; un resultado bonito no cumple solo por eso. |
| OCR parcial, tablas o máscaras incorrectas | Corpus por categorías, revisión de casos mixtos y límites declarados. |
| Concurrencia, sesiones o procesos retenidos | Inicio secuencial, exclusión, limpieza y pruebas de caída. |
| Datos judiciales enviados a terceros | Procesamiento local por defecto; cualquier servicio externo requiere aprobación específica de tratamiento y transferencia. |
| Salida Adobe reescrita por otro editor | Archivo entregado inmutable desde producción; render solo en copia. |
| «100 % igual» sin criterio medible | Separar procedencia, equivalencia funcional e identidad binaria; aprobar criterios antes del despliegue. |

## 11. Entregables y orden recomendado

| Orden | Entregable | Condición para avanzar |
|---|---|---|
| 1 | Decisión de licencia/modalidad y referencia XI controlada | G0 + G1 |
| 2 | Prueba del motor que cumpla apariencia y edición | G2 |
| 3 | Integración mínima compartida en Laravel con trazabilidad | G3 |
| 4 | Corpus y regresión visual/estructural/OCR | G4 |
| 5 | Aislamiento, operación, documentación y aceptación en Word | G5 |
| Opcional y separado | Mejora provisional identificada de LibreOffice | No sustituye ninguno de los hitos finales Adobe |

No asignar fechas ni capacidad concurrente antes de resolver licencia, entorno, ejecutor y mediciones. No migrar el sistema entero ni construir un motor PDF propio para intentar imitar a XI.

### Definición de terminado

El proyecto solo declarará cumplido este objetivo cuando:

- La modalidad de uso del motor esté autorizada y técnicamente validada.
- La procedencia declarada corresponda al motor realmente utilizado.
- Las salidas superen **los dos objetivos** sobre el corpus aprobado y el Word objetivo.
- No haya pérdidas u ocultaciones de contenido ni fallbacks silenciosos.
- Los archivos entregados correspondan íntegramente a los resultados aprobados.
- Seguridad, retención, recuperación y operación estén verificadas.
- Las limitaciones restantes sean conocidas y aceptadas explícitamente, no ocultas tras un estado `ready`.

## 12. Fuentes y límites de las afirmaciones

Documentación oficial consultada mediante Context7:

1. [Acrobat SDK overview — Technical and licensing limitations](https://opensource.adobe.com/dc-acrobat-sdk-docs/library/overview/index.html): limitaciones del uso de Acrobat como servidor y alcance general de IAC. La documentación consultada es la publicada actualmente; confirmar además las condiciones específicas de XI y del contrato aplicable.
2. [JavaScript API — Doc.saveAs](https://opensource.adobe.com/dc-acrobat-sdk-docs/library/jsapiref/doc.html): `cConvID`, `com.adobe.acrobat.docx`, rutas seguras y restricciones de contexto. No establece por sí sola autorización de servidor ni reproducción de todos los ajustes de XI.
3. [Acrobat X changes](https://opensource.adobe.com/dc-acrobat-sdk-docs/library/jsapiref/JS_API_AcroJSChanges.html): incorporación del destino DOCX. No sustituye una prueba en la instalación exacta.
4. [LibreOffice PDF import](https://github.com/LibreOffice/core/blob/master/sdext/source/pdfimport/README.md): recorrido de operaciones PDF, árbol, optimización y generadores XML; no promesa de paridad con XI.
5. [Laravel 13 queues](https://github.com/laravel/docs/blob/13.x/queues.md): relación de tiempos de proceso/trabajo y `retry_after`.

La arquitectura Solid, versiones, funciones y hallazgos de DOCX proceden del análisis local documentado, no de una certificación de Adobe. El código actual del repositorio confirma el recorrido LibreOffice y las validaciones existentes. **Este documento no afirma que ya se haya integrado, licenciado o ejecutado el motor Adobe.**
