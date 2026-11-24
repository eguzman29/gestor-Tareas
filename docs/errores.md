# Catálogo de errores

Todos los errores de la API salen en JSON con el mismo formato, incluidos los inesperados (500). Nunca se muestran trazas, rutas de archivos ni SQL.

```json
{
  "error": "Mensaje para la persona que usa la API",
  "detalles": ["campo: qué falló"]
}
```

Los mensajes de esta página están copiados tal cual de `api.php`.

La única respuesta de error sin cuerpo es el `403` a un `OPTIONS` (pregunta previa de CORS) que llega de un origen que no está en `CORS_ORIGIN`. Ahí el navegador es quien bloquea la respuesta.

---

## Errores por código

| Código | Cuándo pasa | Respuesta exacta |
|:---:|---|---|
| **400** | El cuerpo no es un JSON válido | `{"error": "El cuerpo debe ser un JSON válido.", "detalles": ["cuerpo: JSON mal formado"]}` |
| **401** | Falta el encabezado `Authorization`, o no empieza con `Bearer ` | `{"error": "Falta el token.", "detalles": ["Authorization: usa Bearer <token>"]}` |
| **401** | El token está alterado, mal formado o vencido (dura 30 min) | `{"error": "El token no es válido o ya expiró.", "detalles": ["token: inválido"]}` |
| **401** | El token es válido pero su usuario ya no existe en la base | `{"error": "El token no es válido.", "detalles": ["usuario: no existe"]}` |
| **401** | `POST /login` con usuario o clave incorrectos | `{"error": "Usuario o clave incorrectos.", "detalles": []}` |
| **403** | El rol no tiene permiso: un `usuario` intenta `PUT /tasks/{id}` (solo `admin`) | `{"error": "Tu rol no puede hacer esto.", "detalles": ["rol: permiso insuficiente"]}` |
| **403** | `OPTIONS` desde un origen que no está en la lista de CORS | Sin cuerpo |
| **404** | La tarea no existe **o es de otro usuario**. Se responde igual para no confirmar que existe | `{"error": "La tarea no existe.", "detalles": ["id: no encontrado"]}` |
| **404** | La ruta no existe | `{"error": "La ruta no existe.", "detalles": ["ruta: no encontrada"]}` |
| **405** | Método no permitido en una ruta `/tasks...` (por ejemplo `DELETE /tasks`) | `{"error": "Método no permitido.", "detalles": ["método: usa GET, POST, PUT, PATCH o DELETE según la ruta"]}` |
| **409** | Regla de negocio en `POST /tasks`: el usuario ya tiene una tarea con el mismo `titulo` y `fecha_limite` | `{"error": "La tarea ya existe.", "detalles": ["titulo y fecha_limite: ya están registrados"]}` |
| **409** | La misma regla en `PUT` y `PATCH`: el cambio choca con otra tarea del mismo dueño | `{"error": "Ya tienes otra tarea con ese título y fecha.", "detalles": ["titulo y fecha_limite: ya están registrados"]}` |
| **422** | Los datos de una tarea no cumplen las reglas (`POST`, `PUT`, `PATCH`). Se listan todos los campos que fallan | `{"error": "Los datos de la tarea no son válidos.", "detalles": ["titulo: debe ser texto de 3 a 100 caracteres", "prioridad: debe ser un entero entre 1 y 5"]}` |
| **422** | El JSON es válido pero no es un objeto ni una lista (por ejemplo `"hola"`, `5` o `null`) | `{"error": "El cuerpo tiene una forma inválida.", "detalles": ["cuerpo: debe ser un objeto JSON"]}` |
| **422** | El `id` de la ruta no es un entero positivo (`/tasks/abc`) | `{"error": "El id no es válido.", "detalles": ["id: debe ser un entero positivo"]}` |
| **422** | `POST /reportes` sin `descripcion` de 2 a 300 caracteres | `{"error": "Los datos no son válidos.", "detalles": ["descripcion: debe tener de 2 a 300 caracteres"]}` |
| **422** | `POST /tareas/prioridad` sin `titulo` de 3 a 100 caracteres | `{"error": "Los datos no son válidos.", "detalles": ["titulo: de 3 a 100 caracteres"]}` |
| **500** | Error inesperado de nuestro código. Se registra en la consola del servidor; el cliente no ve detalles | `{"error": "Error interno del servidor.", "detalles": []}` |
| **500** | No se pudo leer el archivo `openapi.json` al pedir `/openapi.json` | `{"error": "No se pudo leer el contrato OpenAPI.", "detalles": []}` |
| **502** | No hubo conexión con la IA (servidor apagado, DNS, red) | `{"error": "No se pudo conectar con la IA.", "detalles": []}` |
| **502** | La IA respondió con un código de error (400, 429, 5xx...) y no se recuperó con los reintentos | `/reportes`: `{"error": "La IA no está disponible (respondió 503).", "detalles": []}` · `/tareas/prioridad`: `{"error": "La IA no está disponible (respondió 503). Intenta más tarde.", "detalles": []}` |
| **502** | La IA respondió algo que no es JSON (por ejemplo HTML) | `{"error": "La IA respondió algo que no entendemos.", "detalles": []}` |
| **502** | La IA respondió JSON, pero sin los campos esperados o con una prioridad fuera de `alta`, `media` o `baja` | `{"error": "La IA respondió algo inesperado.", "detalles": []}` |
| **503** | Falta la variable `JWT_SECRET`. Afecta a **toda** la API, excepto `/docs`, `/openapi.json` y CORS | `{"error": "Falta configurar la variable de entorno JWT_SECRET.", "detalles": []}` |
| **503** | Falta `GEMINI_API_KEY` y se está llamando a Google. Solo afecta a los endpoints con IA; el CRUD sigue funcionando | `/reportes`: `{"error": "Falta configurar la variable GEMINI_API_KEY.", "detalles": []}` · `/tareas/prioridad`: `{"error": "Falta configurar la variable de entorno GEMINI_API_KEY.", "detalles": ["GEMINI_API_KEY: no definida"]}` |
| **503** | `POST /reportes` en un PHP sin la extensión cURL | `{"error": "La integración con IA no está disponible.", "detalles": ["servidor: falta la extensión cURL de PHP"]}` |
| **504** | La IA tardó más de 5 segundos (`CURLOPT_TIMEOUT => 5`). El timeout no se reintenta | `/reportes`: `{"error": "La IA tardó demasiado en responder.", "detalles": []}` · `/tareas/prioridad`: `{"error": "La IA tardó demasiado en responder. Intenta en un momento.", "detalles": []}` |

### Todos los detalles posibles del 422 de una tarea

| Detalle | Causa |
|---|---|
| `titulo: es obligatorio` (igual para `estado`, `prioridad` y `fecha_limite`) | Falta el campo en `POST` o `PUT` |
| `titulo: debe ser texto de 3 a 100 caracteres` | No es texto o su largo está fuera de rango |
| `estado: debe ser pendiente, en_progreso o terminada` | Valor fuera de la lista |
| `prioridad: debe ser un entero entre 1 y 5` | No es entero (`"3"`, `3.5` o `true` no valen) o está fuera de rango |
| `fecha_limite: debe tener formato AAAA-MM-DD` | No es una fecha real con ese formato (`2026-02-30` no vale) |
| `fecha_limite: no puede ser anterior a hoy` | Fecha válida pero ya pasada (regla de negocio) |

---

## Reintentos ante la IA

Antes de responder 502, la API reintenta solo las fallas pasajeras:

| Endpoint | Intentos | Reintenta con | Espera entre intentos | No reintenta |
|---|:---:|---|---|---|
| `POST /reportes` | 2 | 429 y 503 | Ninguna | Timeout, falta de conexión, 400 y demás errores |
| `POST /tareas/prioridad` | 3 | 429, 500, 502, 503 y 504 | 0.5 s y luego 1 s | Timeout, falta de conexión, 400 y demás errores |

Un timeout no se reintenta: con dos intentos de 5 s el usuario esperaría 10 s o más.

---

## Respuestas de éxito

| Código | Cuándo |
|:---:|---|
| **200** | `POST /login`, `GET /tasks`, `GET /tasks/{id}`, `PUT /tasks/{id}`, `PATCH /tasks/{id}`, `POST /reportes` y `POST /tareas/prioridad` |
| **201** | `POST /tasks`: devuelve la tarea creada con su `id` |
| **204** | `DELETE /tasks/{id}` (sin cuerpo) y la pregunta previa `OPTIONS` desde un origen permitido |
