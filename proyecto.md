# Gestor personal de tareas: API REST (Programación Distribuida, U3 S2, BUG-142)

Equipo: Said López Jr (matrícula 20205276), Ethan Carbajal, Jonathan Flores.
Repo: https://github.com/eguzman29/gestor-Tareas
Stack: [lenguaje y framework]. Archivo principal: [ruta]. Comando para correr: [comando].
Datos en memoria (la base de datos es el jueves). Entrega: miércoles 4 de noviembre, 23:59.

## 1. Qué es el proyecto
Ayuda a llevar el control de tareas de cada persona para organizarse mejor. Cada usuario autenticado solo ve y modifica **sus** tareas.

## 2. Ficha de la sesión 1 (ya corregida con el code review)
Endpoints originales: POST /auth/register, POST /auth/login, GET/POST /tasks, GET/PUT/PATCH/DELETE /tasks/{id}.

Correcciones aplicadas en esta sesión:
- **Recursos:** el recurso es **tareas** (y usuarios). "Usuarios Individuales / Profesionales" eran tipos de usuario, no recursos: ahora es un campo `tipo` del usuario.
- **409 en registro:** `POST /auth/register` responde 409 si el correo ya existe.
- **Login:** [confirmar qué decidió el equipo: dejar `POST /auth/login` -> 200, o cambiar a `POST /sesiones` -> 201 porque crea una sesión. Debe coincidir con la ficha].
- **Verbos en ruta:** `/auth/register` y `/auth/login` llevan verbo; [confirmar si se dejan o se cambian a `POST /usuarios` y `POST /sesiones`].
- **Endpoint que haría daño si llega dos veces:** `POST /tasks` (crearía tareas duplicadas). Protección: si el mismo usuario ya tiene una tarea con el mismo título y fecha límite, responde 409.
- **Estado:** es **texto de una lista** (`pendiente`, `en_progreso`, `terminada`), no un entero.

## 3. Alcance de esta sesión
Solo el **CRUD de /tasks**: 5 endpoints (listar, consultar, crear, reemplazar con PUT, borrar). PATCH y auth quedan fuera de esta sesión.
Meta: `python probar_api.py` imprime **11 de 11**. Luego rama, push y pull request revisado por un compañero.

## 4. Especificación del recurso Tarea (bloque 03)
Ruta de la colección: `/tasks`. El `id` lo genera la API (nunca viene del cliente). El usuario sale del token JWT.

| Campo | Tipo | Regla | Valor que se rechaza |
|---|---|---|---|
| titulo | texto | Obligatorio, 3 a 100 caracteres | "" |
| estado | uno de una lista | Solo `pendiente`, `en_progreso` o `terminada` | "hecho" |
| prioridad | número entero | Entre 1 y 5 (rechazar también booleanos y textos como "3") | 9 |
| fecha_limite | fecha | Formato AAAA-MM-DD y no anterior a hoy | "mañana" |

Regla de negocio (409): dos tareas del mismo usuario no pueden tener el mismo `titulo` y la misma `fecha_limite`.

Estructura en memoria sugerida: un diccionario `tareas` por id, un contador para generar ids, y cada tarea guarda también `usuario_id` (interno, no viene del cliente).

## 5. Reglas generales de validación
**Los cuatro filtros, en este orden:**
1. ¿Se entiende el mensaje (JSON roto)? -> **400**
2. ¿Están los datos y tienen la forma correcta? -> **422** (si lo que no existe viene en el cuerpo, también es 422)
3. ¿Existe lo que pide la ruta? -> **404**
4. ¿Choca con lo que ya hay? -> **409**
(401 si falta o es inválido el token; 403 si la tarea existe pero es de otro usuario.)

**Formato de error, siempre:**
```json
{"error": "mensaje corto", "detalles": ["campo: qué está mal"]}
```

**Errores que NO se deben cometer (vistos en el bloque 02):**
- Guardar antes de validar. Primero validar todo, después tocar los datos.
- Dejar que un dato malo cause un 500 (convertir con `float()`/`int()` sin validar). Un 500 por culpa del cliente es un bug.
- Revisar faltantes con "es falso": el `0` y el texto vacío son valores distintos de "no vino". Usar `is None` o revisar si la clave existe.
- Responder 200 con un mensaje de error en el cuerpo cuando no existe algo: debe ser 404 real.
- Confiar en que el front valida: la API valida siempre.
- Reportar todos los errores de validación juntos en `detalles`, no solo el primero.

## 6. Los 5 endpoints

### 6.1 GET /tasks (listar)
- **Qué hace:** devuelve las tareas del usuario autenticado, solo las suyas.
- **Éxito:** 200 con una lista JSON. Si no tiene tareas, es `[]` con 200, **no** 404 (una colección vacía existe).
- **Errores:** 401 sin token o token inválido.
- **Cuidado:** filtrar por `usuario_id` del token, no devolver todo el diccionario.

### 6.2 GET /tasks/{id} (consultar)
- **Qué hace:** devuelve una tarea específica.
- **Éxito:** 200 con el objeto de la tarea.
- **Errores y orden:** 401 (sin token) -> 422 (el id no es un entero) -> 404 (no existe) -> 403 (existe pero es de otro usuario).
- **Cuidado:** el 404 va con el código HTTP real, no con 200 y un mensaje.

### 6.3 POST /tasks (crear)
- **Qué hace:** crea una tarea nueva del usuario autenticado.
- **Cuerpo:** `{"titulo": "Entregar práctica", "estado": "pendiente", "prioridad": 3, "fecha_limite": "2026-11-10"}`
- **Éxito:** 201 con la tarea creada, incluyendo el `id` generado. Ignorar cualquier `id` que mande el cliente.
- **Errores y orden:** 401 -> 400 (JSON roto) -> 422 (campos faltantes o con reglas incumplidas, con todos los fallos en `detalles`) -> 409 (mismo título y fecha para ese usuario).
- **No es idempotente:** mandarlo dos veces crearía dos tareas. Por eso existe el 409.
- **Ejemplo 422:** `{"titulo": "", "estado": "hecho", "prioridad": 9, "fecha_limite": "mañana"}`

### 6.4 PUT /tasks/{id} (reemplazar)
- **Qué hace:** reemplaza **toda** la tarea con el cuerpo recibido. A diferencia de PATCH, deben venir todos los campos obligatorios.
- **Cuerpo:** igual que en POST. El `id` sale de la ruta y no se cambia.
- **Éxito:** 200 con la tarea ya reemplazada.
- **Errores y orden:** 401 -> 422 (id inválido o cuerpo inválido) -> 400 (JSON roto) -> 404 -> 403 -> 409 (choca con **otra** tarea del mismo usuario).
- **Es idempotente:** mandarlo dos veces con el mismo cuerpo deja el mismo estado y responde 200 las dos veces.
- **Trampa clave:** al revisar el 409, **excluir la propia tarea** de la comparación; si no, el segundo PUT idéntico daría 409 y dejaría de ser idempotente.
- **Decisión a confirmar:** la regla "fecha no anterior a hoy" también impediría guardar una tarea cuya fecha ya pasó; acordar si se aplica igual en PUT.
- **Validar todo antes de modificar** la tarea guardada.

### 6.5 DELETE /tasks/{id} (borrar)
- **Qué hace:** elimina la tarea.
- **Éxito:** **204 sin cuerpo**.
- **Errores y orden:** 401 -> 422 (id inválido) -> 404 (no existe) -> 403 (es de otro usuario).
- **Es idempotente en estado, no en respuesta:** el segundo DELETE del mismo id responde 404, y eso es correcto. El recurso quedó igual (no existe).

## 7. Pruebas
Archivos: `probar_api.py` y `prueba.json` en [carpeta]. `prueba.json` lleva la matrícula de **cada integrante** (cada quien corre la prueba con la suya), la URL de la API, la colección `/tasks`, un ejemplo válido, uno inválido (`{"titulo": ""}`) y el cambio para el PUT. Si el id no se llama `id`, cambiar `"campo_id"`.
Si una prueba falla, la línea dice qué esperaba y qué recibió. Corregir con eso.
[Confirmar si `probar_api.py` manda token JWT o si hay que simular el usuario durante la sesión.]

## 8. Flujo de entrega
1. Trabajar en una rama (`feat/crud-tasks`) y hacer push.
2. Abrir pull request con "cómo se probó" (la línea `RESULTADO` real de `probar_api.py`).
3. Otro integrante lo revisa con la lista de la slide y lo aprueba. Cada uno corre la prueba con su matrícula.
4. Cada integrante entrega el PDF `U3S2_Validacion_[matrícula].pdf` en Classroom con: reporte de `probar_api.py`, captura, enlace al PR, reglas del bloque 03 y su validación explicada.

## 9. Reparto
[Said: pasa aquí qué endpoints te tocan y cuáles hacen Ethan y Jonathan. Esperar a que el equipo termine los suyos antes de empezar los míos; no tocar sus archivos ni cambiar su estilo.]

## 10. Cómo quiero que me ayudes
- Haz un endpoint a la vez, con cambios pequeños, sin reescribir archivos completos ni tocar lo que ya funciona.
- Explica cada cambio en pocas líneas: tengo que justificar mi código en clase (bloque 05) y declarar el uso de IA en el bloque 06.
- Respeta el estilo del código que ya hizo el equipo (nombres, estructura, formato de errores).
- Para cada validación, dime qué petición rechaza y con qué código.