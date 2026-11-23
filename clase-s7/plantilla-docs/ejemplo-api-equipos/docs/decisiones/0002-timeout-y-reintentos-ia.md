# 2. Tiempo máximo y reintentos al llamar a la IA

- **Fecha:** 2026-11-12
- **Estado:** Aceptada

## Contexto

La clasificación de reportes depende de Gemini, un servicio externo que puede tardar, fallar o responder algo inesperado. Sin protección, la API se quedaría esperando y el usuario vería una rueda infinita.

## Decisión

Cada llamada espera como máximo 2 segundos y se intenta hasta 3 veces, solo ante fallas pasajeras (timeout, 429 o 5xx). Si no se logra, la API responde 504 (tardó) o 502 (falló) con un mensaje claro.

## Consecuencias

- En el peor caso, el usuario espera unos 8 segundos (3 intentos de 2 s más las pausas entre ellos) y recibe un mensaje que el front puede mostrar.
- Un reporte puede quedar sin clasificar si la IA está caída; el usuario lo vuelve a intentar después.
- Reintentar solo es seguro porque clasificar un reporte no cambia nada fuera de nuestra API.
