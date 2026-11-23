# ADR 0002 · Timeout y reintentos en las llamadas a la IA

- **Fecha:** 2026-11-22
- **Estado:** Aceptada

## Contexto

Dos endpoints dependen de Google Gemini, un servicio externo al que llamamos por internet:

- `POST /reportes` → `preguntar_a_la_ia()`: resume una descripción y le asigna prioridad.
- `POST /tareas/prioridad` → `preguntar_prioridad_a_la_ia()`: sugiere la prioridad a partir del título.

Gemini no lo controlamos. A veces tarda, se satura (responde 429 o 503) o regresa algo que no es lo que pedimos. En clase vimos una llamada a Gemini que tardó 9 s y terminó en 504. Sin protección, la API se quedaría esperando, ocupando el proceso, o respondería un 500 con detalles internos.

Medimos Gemini real el 22 de noviembre de 2026: 5 llamadas a `/tareas/prioridad` tardaron entre **0.8 y 3.2 s**.

## Decisión

1. **Tiempo máximo de 5 s** en cada llamada, con `CURLOPT_TIMEOUT => 5` en las dos funciones. Cubre de sobra lo que medimos (hasta 3.2 s) sin hacer esperar al usuario más de lo razonable.
2. **Reintentar solo las fallas pasajeras**, es decir, las que se pueden arreglar solas en un segundo:

   | Endpoint | Intentos | Reintenta con | Espera |
   |---|:---:|---|---|
   | `/reportes` | 2 | 429 y 503 | Ninguna |
   | `/tareas/prioridad` | 3 | 429, 500, 502, 503 y 504 | 0.5 s y luego 1 s (espera creciente, `usleep`) |

3. **No reintentar:**
   - **Un timeout.** Si ya esperamos 5 s, otro intento haría esperar 10 s o más al usuario (la prueba 22 exige responder en menos de 10 s).
   - **Un 400 u otro error que no es pasajero.** Mandar lo mismo dará el mismo error (prueba 24).
   - **Una falla de conexión.** Se responde de inmediato.
4. **No confiar en la respuesta de la IA.** Antes de usarla se revisa que sea JSON y traiga lo pedido: `prioridad` dentro de `alta`, `media` o `baja` y una `razon` (o `resumen`) no vacía. La razón se recorta a 200 caracteres.
5. **Códigos claros, nunca 500:** 504 si tardó, 502 si falló, no hubo conexión o respondió basura, y 503 si falta `GEMINI_API_KEY`. Todos con el formato `{"error", "detalles"}`.
6. **La clave sale de `GEMINI_API_KEY`**, nunca del código. La dirección sale de `EXTERNO_URL`, para que las pruebas apunten al simulador sin tocar el código.
7. **Sin `Idempotency-Key`.** Pedir una prioridad o un resumen no guarda ni manda nada: es una consulta. Repetirla no duplica nada, así que reintentar es seguro.

## Consecuencias

**A favor**

- El usuario nunca se queda esperando indefinidamente y siempre recibe un mensaje que entiende.
- Una falla de un momento se recupera sola sin que el usuario se entere (prueba 23).
- El CRUD no depende de la IA: si Gemini se cae o falta la clave, crear y consultar tareas sigue funcionando.
- Las fallas se pueden probar sin Google, con el simulador (pruebas 21 a 25 y `probar_ia.py`).

**En contra**

- Si Gemini algún día tarda más de 5 s de forma normal, todas esas respuestas se perderían con 504 y habría que subir el límite.
- En el peor caso, `/tareas/prioridad` puede tardar más de 5 s: si los tres intentos reciben un 503 justo antes del límite, serían 5 + 0.5 + 5 + 1 + 5 = 16.5 s. El timeout limita cada intento, no la suma.
- Las dos funciones tienen reglas de reintento distintas porque las escribieron personas diferentes en sesiones distintas. Queda pendiente unificarlas en una sola función.
