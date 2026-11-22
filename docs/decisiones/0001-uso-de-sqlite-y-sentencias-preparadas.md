# ADR 0001 · SQLite con sentencias preparadas y aislamiento por dueño

- **Fecha:** 2026-11-22
- **Estado:** Aceptada

## Contexto

La API tiene que guardar usuarios y tareas, y los datos tienen que sobrevivir a un reinicio. Al principio (sesión 2) se guardaban en un archivo JSON, que se reescribía completo en cada cambio y no permitía filtrar ni garantizar que no hubiera duplicados.

Además:

- Es un proyecto de clase que cada integrante corre en su computadora: no queremos instalar ni configurar un servidor de base de datos.
- Cada usuario solo debe ver y cambiar **sus** tareas. El administrador puede ver y corregir todas.
- Los datos llegan del cliente (título, id en la ruta, usuario en el login), así que hay que evitar la **inyección SQL**.

## Decisión

1. **SQLite con PDO.** La base es un archivo, `tasks.sqlite`, que `conectar_db()` crea sola la primera vez junto con las tablas `usuarios` y `tasks` y los usuarios de prueba. PDO lanza excepción ante cualquier error (`ERRMODE_EXCEPTION`).
2. **Todas las consultas que reciben datos usan `prepare` y `?`.** Nunca se pega un valor al texto del SQL. Por ejemplo, en `buscar_tarea()`:
   ```php
   $consulta = $db->prepare('SELECT ... FROM tasks WHERE id = ? AND usuario_id = ?');
   $consulta->execute([$id, $usuario_id]);
   ```
   Las únicas sentencias sin `?` (los `CREATE TABLE`, `SELECT COUNT(*) FROM usuarios` al sembrar y la lista completa del admin) no reciben ningún dato del cliente.
3. **Aislamiento por dueño en la consulta.** Para un `usuario`, `leer_tareas()`, `buscar_tarea()`, `actualizar_tarea()` y `borrar_tarea()` filtran con `usuario_id = ?`. Para el `admin` el filtro se omite a propósito (`$alcance = null`), porque su rol es ver y corregir todo.
4. **El `usuario_id` sale del token, nunca del cuerpo.** `usuario_actual()` verifica el JWT y `usuario_id()` busca el id a partir del `sub` del token. Aunque el cliente mande un `usuario_id` en el JSON, se ignora.
5. **Una tarea ajena responde 404, no 403.** Si la consulta con `usuario_id = ?` no la encuentra, la API dice "La tarea no existe." Así no confirma que el id existe y es de otra persona.
6. **La regla de duplicados tiene dos barreras.** `existe_duplicado()` revisa antes de guardar (409) y la tabla tiene `UNIQUE (usuario_id, titulo, fecha_limite)`. Si dos peticiones iguales llegan casi al mismo tiempo (doble clic en "Guardar"), la segunda choca con el `UNIQUE`, se atrapa el `PDOException` y también responde 409.
7. **Contraseñas con `password_hash` (bcrypt)** y revisión con `password_verify`. En la tabla nunca está la clave en texto.

## Consecuencias

**A favor**

- No hay nada que instalar: PHP ya trae `pdo_sqlite`. Clonar y arrancar.
- Los datos sobreviven al reinicio de la API (prueba 15 de `probar_api.py`).
- Sin inyección SQL: un título como `x' OR '1'='1` se guarda como texto, no se ejecuta.
- Un usuario no puede ver, cambiar ni borrar lo de otro, ni siquiera saber que existe (prueba 20).
- El doble clic no crea tareas repetidas.

**En contra**

- SQLite deja **un solo escritor a la vez**: bloquea el archivo mientras escribe. Con muchos usuarios escribiendo al mismo tiempo las peticiones se harían fila.
- El archivo vive en la misma máquina que la API. Si quisiéramos **varios servidores** de la API detrás de un balanceador, no podrían compartirlo; habría que migrar a PostgreSQL o MySQL. Como todo pasa por PDO, el cambio sería sobre todo la cadena de conexión y el `CREATE TABLE`.
- No hay migraciones: si cambia la tabla, hay que borrar `tasks.sqlite` o modificarla a mano.
