# Changelog

Todos los cambios importantes de este proyecto se documentan en este archivo.

El formato se basa en [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) y el proyecto usa [Versionado Semántico](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-11-22

### Agregado

- CRUD de tareas (`GET`, `POST`, `PUT`, `PATCH` y `DELETE` en `/tasks`) con validación de cada campo (422 que dice qué campo falló) y un formato de error único `{"error", "detalles"}`.
- Regla de negocio: un usuario no puede tener dos tareas con el mismo título y fecha límite (409), revisada en `existe_duplicado()` y respaldada por una restricción `UNIQUE` en la tabla; la fecha límite no puede ser anterior a hoy.
- Persistencia en SQLite con PDO y consultas parametrizadas (`?`); los datos sobreviven al reinicio.
- Login con JWT (HS256, vence en 30 minutos) y contraseñas con `password_hash` (bcrypt).
- Roles `admin` y `usuario`: el usuario solo ve y modifica sus tareas (una ajena responde 404) y no puede usar `PUT` (403).
- `POST /tareas/prioridad`: la IA (Gemini) sugiere la prioridad de una tarea y una razón a partir de su título.
- `POST /reportes`: la IA lee una descripción, le asigna prioridad (alta, media o baja) y la resume.
- Las dos llamadas a la IA tienen un tiempo máximo de 5 s (504), responden 502 si la IA falla o responde algo inválido, leen la clave de `GEMINI_API_KEY` y reintentan solo fallas pasajeras: `/reportes` 2 intentos ante 429 y 503; `/tareas/prioridad` hasta 3 intentos ante 429, 500, 502, 503 y 504, esperando 0.5 s y 1 s. El timeout no se reintenta.
- CORS con una lista de orígenes (`CORS_ORIGIN`), `Authorization` y `Content-Type` permitidos, y respuesta a la pregunta previa `OPTIONS` antes de revisar el token.
- Página web en `front/index.html`: login, lista, alta con errores por campo y consulta a la IA (`/reportes`).
- Contrato OpenAPI servido en `/docs` y `/openapi.json`, con los 9 endpoints.
- Manejo de errores inesperados: cualquier excepción responde 500 en JSON con el formato de siempre, sin trazas ni rutas de archivos (`set_exception_handler`).
- `JWT_SECRET` obligatorio: el secreto de los tokens ya no tiene un valor escrito en el código; si falta, la API responde 503.
- Documentación: README, `.env.example`, arquitectura C4 (`docs/arquitectura.md`), catálogo de errores (`docs/errores.md`) y dos decisiones de arquitectura (`docs/decisiones/`).
- Pruebas automáticas con `probar_api.py` (28 de 28) y `probar_ia.py` (4 de 4).

### Cambiado

- El almacenamiento pasó de un archivo JSON a SQLite (sesión 3).
- El usuario de cada petición sale del token, ya no de un usuario fijo en el código (sesión 4).
- El contrato se corrigió para que cada endpoint documente exactamente los códigos que responde, y sus fechas de ejemplo son futuras.

### Eliminado

- `openapi.yaml`: el único contrato es `openapi.json`, que es el que se sirve en `/docs`.
- Material de clase y reportes de sesiones anteriores que no forman parte del proyecto (zips, guías y reportes de las sesiones 3 a 5).

### Limitaciones conocidas

Decisiones conscientes de esta versión, no errores:

- Las rutas mezclan idiomas (`/tasks` y `/tareas/prioridad`): se dejaron así para no romper el front ni las pruebas, que ya usan esos nombres.
- Las dos llamadas a la IA tienen reglas de reintento distintas, porque cada una se hizo en una sesión diferente. Las dos cumplen la regla de reintentar solo fallas pasajeras (ver `docs/decisiones/0002-timeout-y-reintentos-ia.md`).
- `GET /tasks` regresa todas las tareas sin paginación y no hay un límite de peticiones por usuario. Con el volumen de un gestor personal no hace falta; en una empresa serían lo siguiente por agregar.
