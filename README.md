# Gestor de tareas · API

API REST para que cada persona organice sus tareas (título, estado, prioridad y fecha límite) desde una página web, con inicio de sesión, roles y ayuda de una IA que sugiere la prioridad de una tarea o resume una descripción. Cada usuario solo ve y modifica sus propias tareas; el administrador puede ver y corregir todas.

- **Versión:** v1.0.0 (ver [CHANGELOG.md](CHANGELOG.md))
- **Lenguaje:** PHP 8.1+ sin framework, SQLite con PDO y cURL
- **Arquitectura:** [docs/arquitectura.md](docs/arquitectura.md) (diagrama C4) y decisiones en [docs/decisiones/](docs/decisiones/)
- **Errores:** [docs/errores.md](docs/errores.md)

## Cómo correrla

1. Clonar el repositorio:
   ```
   git clone https://github.com/eguzman29/gestor-Tareas.git
   cd gestor-Tareas
   ```
2. Instalar dependencias: **nada**. Solo hace falta PHP 8.1 o superior con las extensiones `pdo_sqlite`, `curl` y `mbstring` (revisar con `php -m`), y Python 3 para las pruebas.
3. Configurar las variables de entorno en la terminal donde se va a arrancar la API (ver la tabla de abajo). PHP no lee el archivo `.env`: cada variable se define a mano.

   Windows (cmd):
   ```
   set JWT_SECRET=un-secreto-largo
   set GEMINI_API_KEY=tu-clave-de-ai-studio
   ```
   Mac o Linux:
   ```
   export JWT_SECRET="un-secreto-largo"
   export GEMINI_API_KEY="tu-clave-de-ai-studio"
   ```
4. Arrancar la API (la base `tasks.sqlite` y los usuarios de prueba se crean solos la primera vez):
   ```
   php -S 127.0.0.1:8010 api.php
   ```
5. Abrir el contrato en http://127.0.0.1:8010/docs (Swagger se carga desde internet, así que esa página necesita conexión).
6. Abrir el front **desde un servidor**, en otra terminal (con doble clic el origen sería `null` y CORS lo bloquea):
   ```
   cd front
   python3 -m http.server 5500
   ```
   y en el navegador: http://127.0.0.1:5500

### Usuarios de prueba

| Usuario | Clave | Rol |
|---|---|---|
| `encargado` | `encargado123` | admin: ve y modifica todas las tareas |
| `ana` | `ana12345` | usuario: solo sus tareas, no puede usar `PUT` |
| `beto` | `beto12345` | usuario |

## Variables de entorno

Están todas en [.env.example](.env.example). **Ninguna clave real va en el repositorio.**

| Variable | Para qué sirve | Obligatoria |
|---|---|---|
| `JWT_SECRET` | Secreto con el que se firman los tokens (HS256). No hay ninguno escrito en el código: si falta, la API responde 503 | Sí |
| `GEMINI_API_KEY` | Clave de Google AI Studio para `/reportes` y `/tareas/prioridad`. Si falta y se llama a Google, esos dos endpoints responden 503 y el resto de la API funciona | Sí, para la IA |
| `GEMINI_MODELO` | Modelo de Gemini. Por defecto `gemini-flash-lite-latest` | No |
| `EXTERNO_URL` | A dónde llama la API para la IA. Por defecto Google; para probar fallas, el simulador `http://127.0.0.1:9000` | No |
| `CORS_ORIGIN` | Páginas que pueden usar la API desde el navegador, separadas por coma. Por defecto `http://127.0.0.1:5500,http://localhost:5500` | No |

## Endpoints

| Método y ruta | Qué hace | Respuestas |
|---|---|---|
| `POST /login` | Cambia usuario y clave por un token JWT que vence en 30 minutos | 200, 400, 401, 422 |
| `GET /tasks` | Lista las tareas (el usuario ve las suyas; el admin, todas) | 200, 401 |
| `GET /tasks/{id}` | Consulta una tarea | 200, 401, 404, 422 |
| `POST /tasks` | Crea una tarea | 201, 400, 401, 409, 422 |
| `PUT /tasks/{id}` | Reemplaza una tarea completa (solo admin) | 200, 400, 401, 403, 404, 409, 422 |
| `PATCH /tasks/{id}` | Cambia solo algunos campos | 200, 400, 401, 404, 409, 422 |
| `DELETE /tasks/{id}` | Borra una tarea | 204, 401, 404, 422 |
| `POST /tareas/prioridad` | La IA sugiere la prioridad (alta, media o baja) y una razón a partir del título | 200, 400, 401, 422, 502, 503, 504 |
| `POST /reportes` | La IA lee una descripción, le asigna prioridad (alta, media o baja) y la resume. Es el que usa la página web | 200, 400, 401, 422, 502, 503, 504 |

Además, cualquier ruta responde 503 si falta `JWT_SECRET` y 500 si ocurre un error inesperado.

Una tarea tiene `titulo` (3 a 100 caracteres), `estado` (`pendiente`, `en_progreso` o `terminada`), `prioridad` (entero de 1 a 5) y `fecha_limite` (`AAAA-MM-DD`, no anterior a hoy).

## Ejemplos de uso

Entrar y obtener el token:
```
curl -X POST http://127.0.0.1:8010/login -H "Content-Type: application/json" -d "{\"usuario\": \"ana\", \"clave\": \"ana12345\"}"
```

Crear una tarea con ese token:
```
curl -X POST http://127.0.0.1:8010/tasks -H "Authorization: Bearer TU_TOKEN" -H "Content-Type: application/json" -d "{\"titulo\": \"Estudiar para el examen\", \"estado\": \"pendiente\", \"prioridad\": 4, \"fecha_limite\": \"2026-12-15\"}"
```

Pedirle a la IA la prioridad de una tarea:
```
curl -X POST http://127.0.0.1:8010/tareas/prioridad -H "Authorization: Bearer TU_TOKEN" -H "Content-Type: application/json" -d "{\"titulo\": \"Entregar el reporte de practicas hoy mismo\"}"
```

## Contrato y errores

- Contrato OpenAPI: http://127.0.0.1:8010/docs (Swagger, con botón Authorize), servido desde [openapi.json](openapi.json). Es el único contrato del proyecto.
- Errores: todos responden `{"error": "...", "detalles": [...]}`, incluso los inesperados (500), sin trazas ni rutas de archivos. La única excepción es el 403 sin cuerpo a la pregunta previa de CORS (`OPTIONS`) desde un origen no permitido. Ver [docs/errores.md](docs/errores.md).

## Cómo probarla

Con la API arrancada en una terminal donde antes se definió `EXTERNO_URL` (para que las pruebas de la IA usen el simulador de fallas):

Windows (cmd):
```
set JWT_SECRET=un-secreto-largo
set EXTERNO_URL=http://127.0.0.1:9000
php -S 127.0.0.1:8010 api.php
```
Mac o Linux:
```
export JWT_SECRET="un-secreto-largo"
export EXTERNO_URL=http://127.0.0.1:9000
php -S 127.0.0.1:8010 api.php
```

En otra terminal, en la carpeta del proyecto:
```
python3 probar_api.py
```

La primera vez la prueba 15 queda pendiente: se reinicia la API (Ctrl+C y arrancarla otra vez en la misma terminal) y se corre de nuevo. `python3 probar_ia.py` prueba solo el endpoint con IA en 4 situaciones (funciona, lenta, caída y basura). Los dos scripts arrancan el simulador solos.

Resultado de la versión v1.0.0: **28 de 28 pruebas** (`probar_api.py`) y **4 de 4** (`probar_ia.py`).

## Anatomía de mi API

```
Anatomía de mi API · lista de revisión (43 de 43)

0 · Cliente (2/2)
  [x] Mi proyecto tiene un cliente que consume la API: una página, una app o un script.
  [x] Puedo hacer una acción en el cliente y mostrar la petición y la respuesta en la pestaña Red (F12).
  En la presentación: Abran su front y hagan una acción en vivo. Con F12 → Red, muestren la petición que salió y la respuesta que llegó.

1 · Petición HTTP (3/3)
  [x] Mis rutas nombran cosas (/tareas, /tareas/7), no acciones (/crearTarea).
  [x] Cada operación usa el método correcto: GET lee, POST crea, PUT o PATCH modifica, DELETE borra.
  [x] Tengo la ficha de endpoints: método, ruta, qué recibe y qué responde.
  En la presentación: Muestren su ficha de endpoints o /docs y expliquen en una frase qué hace cada ruta.

2 · CORS (3/3)
  [x] Configuré CORS con una lista de orígenes, no con *.
  [x] Authorization y Content-Type están en los encabezados permitidos.
  [x] CORS va antes de revisar el token.
  En la presentación: Abran su front desde su origen (por ejemplo, el puerto 5500) con F12 abierto: la consola sin errores de CORS.

3 · Autenticación (3/3)
  [x] Las contraseñas se guardan con hash (bcrypt o password_hash).
  [x] El login regresa un token JWT con vencimiento.
  [x] Sin token, o con un token alterado, la API responde 401.
  En la presentación: Hagan una petición a una ruta protegida sin token (401) y luego con token (200).

4 · Autorización (2/2)
  [x] Hay al menos dos roles y una acción que un rol no puede hacer (403).
  [x] Las consultas de recursos propios filtran por el usuario del token (WHERE id = ? AND usuario = ?).
  En la presentación: Con el usuario A pidan un recurso del usuario B: debe responder 404. Con un rol sin permiso intenten borrar: 403.

5 · Validación (3/3)
  [x] Valido campos obligatorios, tipo, formato y rango.
  [x] Un dato inválido responde 422 y dice qué campo falló.
  [x] Un duplicado o una regla rota responde 409.
  En la presentación: Manden un dato inválido desde su front y muestren que el error aparece en el campo correcto.

6 · Endpoint / controlador (2/2)
  [x] Tengo el CRUD completo de mi recurso principal.
  [x] Cada operación responde con su código: 200, 201, 204 o 404.
  En la presentación: Recorran el CRUD de su recurso principal en /docs, Postman o su front.

7 · Servicio / lógica de negocio (2/2)
  [x] Identifico al menos una regla de negocio propia de mi proyecto.
  [x] La regla está en una función aparte, no revuelta con el manejo de HTTP.
  En la presentación: Expliquen su regla de negocio y muestren el caso en que dice que no (409).

7b · Integraciones externas (5/5)
  [x] Mi API consume al menos un servicio externo.
  [x] La llamada tiene un tiempo máximo de espera (timeout).
  [x] Si el servicio falla o tarda, responde 502 o 504 con un mensaje claro.
  [x] La clave del servicio está en una variable de entorno.
  [x] Puedo explicar en qué caso se usaría SOAP en lugar de REST (por ejemplo, el SAT).
  En la presentación: Muestren la integración funcionando y luego la falla controlada: sin clave (503) o con el servicio caído (502 o 504).

8 · Persistencia (2/2)
  [x] Los datos se guardan en una base de datos y sobreviven al reinicio.
  [x] Todas mis consultas usan parámetros (?), ninguna pega datos al texto del SQL.
  En la presentación: Reinicien la API en vivo y muestren que los datos siguen ahí. Enseñen una de sus consultas con ?.

9 · Respuesta HTTP (2/2)
  [x] Todos mis errores salen en JSON con el mismo formato: {"error": ..., "detalles": [...]}.
  [x] Ningún error muestra trazas, rutas de archivos ni SQL.
  En la presentación: Provoquen dos errores distintos y muestren que ambos llegan con el mismo formato JSON.

Configuración base (3/3)
  [x] Secretos y claves (JWT, IA, base de datos) se leen de variables de entorno, no están escritos en el código.
  [x] En mi repositorio de GitHub no hay ninguna clave.
  [x] Mi README dice qué variables hay que configurar.
  En la presentación: Muestren dónde lee su código las variables de entorno y busquen en vivo en su repositorio que no haya ninguna clave.

Pruebas automáticas (2/2)
  [x] Tengo pruebas automáticas (probar_api.py, una colección de Postman o similar).
  [x] Las pruebas pasan antes de entregar.
  En la presentación: Corran sus pruebas en vivo y comenten el resultado.

Documentación (6/6)
  [x] Mi README explica qué es, cómo correrla en pocos pasos, qué variables configurar y cómo probarla.
  [x] Mi API publica su contrato (OpenAPI en /docs u openapi.yaml).
  [x] Tengo .env.example con las variables de entorno, sin valores secretos.
  [x] Tengo docs/arquitectura.md con el diagrama C4 de contenedores: página, API, base de datos y servicio externo.
  [x] Tengo CHANGELOG.md con lo que trae la versión v1.0.0.
  [x] Recomendado: 2 decisiones explicadas (ADR) en docs/decisiones/ y el catálogo de errores en docs/errores.md.
  En la presentación: Abran su repositorio en GitHub: el README y el diagrama de arquitectura. Con el diagrama expliquen qué parte de su sistema está distribuida y cómo se comunican las piezas.

Timeout e idempotencia (2/2)
  [x] Sé qué operaciones de mi API se pueden repetir sin riesgo y cuáles no.
  [x] Si reintento llamadas externas, solo es ante fallas pasajeras (429, 5xx o timeout).
  En la presentación: Expliquen qué pasaría en su proyecto si el usuario le da doble clic a "Guardar".

Seguridad en capas (1/1)
  [x] Puedo decir qué riesgo del OWASP API Top 10 evita cada capa de mi API.
  En la presentación: Cierren la presentación con la tabla “riesgo → cómo lo evita mi API”.
```

## Qué se puede repetir sin riesgo

| Operación | ¿Repetirla causa daño? | Por qué |
|---|:---:|---|
| `GET /tasks`, `GET /tasks/{id}` | No | Solo leen |
| `PUT /tasks/{id}` | No | Deja la tarea con los mismos valores las veces que sea |
| `PATCH /tasks/{id}` | No | Pone los campos enviados en el mismo valor; no suma ni acumula |
| `DELETE /tasks/{id}` | No | La primera vez borra (204); las siguientes responden 404 y no borran nada más |
| `POST /tareas/prioridad`, `POST /reportes` | No | Solo consultan a la IA; no guardan nada. Por eso la API puede reintentarlas ante fallas pasajeras |
| `POST /tasks` | **Sí, en principio** | Crearía la tarea dos veces. Lo impide la regla de negocio: la segunda petición con el mismo título y fecha responde **409**. Si las dos llegan casi juntas (doble clic en "Guardar"), la restricción `UNIQUE (usuario_id, titulo, fecha_limite)` de la tabla detiene a la segunda y también responde 409 |

## Seguridad en capas (OWASP API Top 10)

| Riesgo (OWASP API Security Top 10, 2023) | Cómo lo evita nuestra API |
|---|---|
| **API1 · Acceso a objetos de otro usuario** (BOLA) | Las consultas de un `usuario` filtran con `WHERE id = ? AND usuario_id = ?`, y el `usuario_id` sale del token, nunca del cuerpo. Una tarea ajena responde 404, sin confirmar que existe |
| **API2 · Autenticación rota** | Contraseñas con `password_hash` (bcrypt). JWT HS256 que vence en 30 minutos, con firma comparada con `hash_equals`. El secreto sale de `JWT_SECRET`; sin él la API responde 503 en todas las rutas |
| **API3 · Propiedades de más** (asignación masiva o datos de más en la respuesta) | `validar_tarea()` solo deja pasar `titulo`, `estado`, `prioridad` y `fecha_limite`: si el cliente manda `id` o `usuario_id`, se ignoran. `datos_publicos()` quita el `usuario_id` de las respuestas |
| **API4 · Consumo de recursos sin límite** | Timeout de 5 s a la IA y reintentos limitados (2 o 3 intentos). Límites de largo: título de 3 a 100 caracteres y descripción de 2 a 300. No incluye límite de peticiones por usuario ni paginación (ver limitaciones conocidas en el CHANGELOG) |
| **API5 · Funciones de otro rol** (BFLA) | `solo_admin()` en `PUT /tasks/{id}`: un `usuario` recibe 403 |
| **API7 · Que la API haga peticiones a donde diga el cliente** (SSRF) | La dirección de la IA la fija el servidor (`EXTERNO_URL`); el cliente solo manda el texto |
| **API8 · Mala configuración** | CORS con una lista de orígenes, nunca `*`. Errores sin trazas (`display_errors` apagado y `set_exception_handler`). Secretos solo en variables de entorno; `.env` está en `.gitignore` |
| **API9 · Inventario de endpoints** | Un solo contrato, `openapi.json`, con los 9 endpoints, servido en `/docs`. Versión marcada con el tag `v1.0.0` |
| **API10 · Confiar a ciegas en otra API** | La respuesta de Gemini se valida antes de usarla (prioridad dentro de `alta`, `media` o `baja` y texto no vacío) y se recorta a 200 caracteres. Si no cumple: 502 |
| **Inyección SQL** (OWASP Top 10 general) | Todas las consultas con datos del cliente usan `prepare` y `?` |

## REST y SOAP en mi proyecto

Nuestra API es REST: cada recurso tiene su ruta (`/tasks`, `/tasks/7`), el método dice la operación y todo viaja en JSON. Si el gestor de tareas lo usara una empresa para registrar trabajos facturables, podría necesitar consultar el servicio SOAP del SAT para revisar si una factura está vigente antes de marcar la tarea como `terminada`. Esa consulta iría en la misma pieza que Gemini, la integración externa (7b): la API armaría un sobre XML con la operación `Consulta` y los datos de la factura, lo mandaría por POST al servicio del SAT con su encabezado `SOAPAction` y un tiempo máximo de espera, leería del XML de respuesta el campo `Estado` y le contestaría al front en JSON, por ejemplo `{"estado": "Vigente"}`. Si el SAT tarda o falla, respondería 504 o 502 igual que hoy con la IA. El front nunca vería XML: la traducción de JSON a XML y de regreso se queda dentro de la API.

## Equipo y reflexiones

| Integrante | Qué hizo |
|---|---|
| Ethan Carbajal | Base del CRUD (`GET` y `POST`) y validación, persistencia en SQLite, contrato OpenAPI y Swagger, login con JWT y roles, `POST /reportes` con IA, CORS y página web, primera versión de `.env.example` y del catálogo de errores |
| Said López Jr. | `PUT` y `DELETE`, revisión de seguridad (permisos del admin, `PATCH`, filtro por dueño), `POST /tareas/prioridad` con IA, timeout y reintentos, lista de orígenes de CORS, `JWT_SECRET` obligatorio y errores 500 sin trazas, corrección de `.env.example`, del catálogo de errores y del contrato, arquitectura C4 y decisiones (ADR) |
| Jonathan Flores | Revisión de código y fusión de pull requests, README, CHANGELOG y párrafo de REST y SOAP |

**Ethan Carbajal:** [COMPLETAR: ¿qué pieza te costó más y por qué? ¿Qué harías distinto si este proyecto fuera de una empresa?]

**Said López Jr.:** Lo que más me costó fue la integración con Gemini en `/tareas/prioridad`. Al principio pensaba que reintentar siempre era lo más seguro, pero entendí que no: si la IA ya tardó 5 segundos, volver a intentarlo solo hace esperar más al usuario, y un error 400 va a fallar igual las veces que lo mandes. Por eso solo reintentamos los 429 y 5xx, con una espera corta entre intentos. También aprendí que la documentación se queda atrás del código muy rápido: al revisarla al final encontramos mensajes de error que no coincidían y una validación del título que contaba bytes en lugar de letras, así que fallaba con acentos o con ñ. Si fuera un proyecto de empresa, agregaría registros y alertas para saber cuándo la IA empieza a fallar, un límite de peticiones por usuario y pruebas que comparen el contrato OpenAPI con lo que la API responde de verdad, para que la documentación nunca se desfase.

**Jonathan Flores:** Lo que más me costó fue dejar el entorno funcionando: en mi computadora PHP no traía activadas las extensiones de SQLite y cURL, y la llamada a Gemini daba 502 por un problema de certificados SSL, hasta que lo encontré y lo arreglé. Aprendí que una API depende de muchas piezas fuera del código, y que por eso conviene el timeout de 5 segundos y los reintentos, para que la API responda 502 o 504 en vez de caerse con un 500. Si fuera un proyecto de empresa, usaría una base de datos real con migraciones, guardaría las claves en un gestor de secretos, agregaría pruebas automáticas en cada pull request y usaría una librería probada para el JWT.
