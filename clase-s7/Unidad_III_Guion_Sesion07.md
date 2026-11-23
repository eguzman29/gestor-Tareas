# Unidad III · Sesión 7 · Cierre: su API, pieza por pieza

**Jueves 19 de noviembre de 2026 · 7:15–9:00**

Archivos:

- `u3_sesion07_slides.html`: la presentación, con 16 diapositivas.
- `mapa_api.html`: el mapa interactivo "Anatomía de una API REST". Es el mismo del enlace de Claude.
- `plantilla-docs.zip`: la plantilla de documentación (README, CHANGELOG, `.env.example`, arquitectura C4, errores y ADR), con un ejemplo completo hecho con la API de préstamo de equipos de la clase.

## Antes de clase

1. **Suban `mapa_api.html` y `plantilla-docs.zip` a Classroom** como material de la unidad. La otra opción es compartir el enlace del mapa: los artefactos empiezan privados y se comparten desde su página en claude.ai.
2. **Prueben la consulta SOAP en su laptop**, en cmd y no en PowerShell. Copien `consulta.xml` desde la diapositiva 6, guárdenlo en una carpeta y corran el `curl` de la misma diapositiva, en esa carpeta. Con los datos de ejemplo, el SAT debe responder `No Encontrado`.
   - Si quieren mostrar `Vigente`, usen los datos del QR de una factura de una empresa, no una con su RFC personal: se proyecta.
3. **Revisen la lista de equipos de la diapositiva 13.** Trae los 8 proyectos del kit de la sesión 5. Si PLOTO y Registro son un solo equipo, borren uno antes de sortear. El sorteo se guarda en el navegador con el que lo hagan.

## Minuto a minuto

| Hora | Diap. | Qué pasa | Qué decir |
|---|---|---|---|
| 7:15 | 1 | Portada | "Hoy no hay tema nuevo para su proyecto: la sesión es para que lo terminen bien." |
| 7:17 | 2 | El objetivo de la unidad | "Lo que pide el programa: REST ya lo tienen, SOAP es hoy y la integración documentada es el martes." |
| 7:20 | 3 | El viaje de una petición | Recorran del 0 al 9. Detenerse en la línea roja: "Si falla en la puerta, ya no sigue: regresa con su código". Y en la base: "La configuración no es un paso de cada petición". |
| 7:25 | 4 | Mapa interactivo, **en vivo** | Abran `mapa_api.html#autenticacion`. Muestren las 4 pestañas y "Ver una buena respuesta". En "En mi proyecto", marquen una casilla y den clic en "Copiar mi lista". |
| 7:30 | 5 | SOAP: definición y tabla | "Mismo HTTP, otra forma de hablar: una operación dentro de un XML, casi siempre POST." |
| 7:35 | 6 | El caso del SAT, **en vivo** | Corran el `curl` en cmd y busquen en la respuesta `Estado`. Si no hay internet, lean la diapositiva. |
| 7:42 | 7 | Dónde se lo van a encontrar | "No van a escoger SOAP: se lo van a encontrar. Se integra como la IA de la sesión 5: una pieza 7b que traduce." |
| 7:45 | 8 | Qué presentan | 10 minutos por equipo: 1 + 6 + 3. Sin diapositivas: el mapa es la guía. |
| 7:47 | 9 | Así documenta un equipo profesional | "Todo vive en el repositorio, junto al código." Abran `plantilla-docs.zip` → `ejemplo-api-equipos` en GitHub o en VS Code para que vean el diagrama C4 dibujado. Obligatorio: README, contrato, `.env.example`, CHANGELOG y arquitectura. Recomendado: errores y 2 ADR. |
| 7:51 | 10 | Qué entregan | Lunes 23, 23:59. Repositorio con `v1.0.0`, la documentación de la plantilla y, en el README, su lista, REST/SOAP y las reflexiones. |
| 7:53 | 11 | Cómo se califica | "La mitad se juega en lo que saben explicar." |
| 7:55 | 12 | Así serán las preguntas | Lean una en voz alta y pregunten a alguien al azar, como ensayo. |
| 7:57 | 13 | **Sorteo** | Den clic en "Sortear el orden" y dejen la diapositiva proyectada para que anoten su hora. |
| 8:00 | 14 | Taller | Dejen la 14 proyectada. Recorran los equipos con una pregunta de la diapositiva 12. |
| 8:50 | 15 | Cierre | Las 3 ideas y las fechas. |

## Preguntas de pasillo durante el taller

- "¿Qué tienen sin marcar en el mapa? ¿Quién se encarga de cada pendiente?"
- "Muéstrenme el 401 de su API. ¿Qué línea lo decide?"
- "Abran su diagrama de arquitectura: ¿qué flecha es un mensaje por la red?"
- "¿Ya tienen `v1.0.0`? ¿Su README dice qué variables de entorno hay que configurar?"
- "¿Quién va a explicar la integración externa el martes?"

## Calificación de la presentación final (100)

| Criterio | Puntos | Qué se espera |
|---|---|---|
| Funciona en vivo | 25 | La petición recorre la API de principio a fin; cada falla responde con su código correcto. |
| Anatomía completa | 25 | La API contempla las piezas del mapa y el equipo lo demuestra: la puerta, la regla de negocio, la integración y la base de datos. |
| Defensa individual | 30 | Cada integrante explica su pieza y su código sin leer, y contesta una pregunta de otra pieza. |
| Documentación y entrega | 20 | Documentación obligatoria de la plantilla (README, contrato, `.env.example`, CHANGELOG, diagrama C4 en `docs/arquitectura.md`), pruebas que pasan, párrafo de REST y SOAP, reflexiones individuales y `v1.0.0` a tiempo. Los ADR y el catálogo de errores son recomendados. |

- Entrega tardía: aplica el descuento de siempre.
- La defensa de las sesiones 5 y 6 queda incluida en el recorrido.
