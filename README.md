# Gestor de tareas

API REST en PHP puro, sin framework.

## Ejecutar

Desde esta carpeta, con PHP 8.1 o superior instalado:

```bash
php -S 127.0.0.1:8000 api.php
```

La implementación actual incluye:

- `GET /tasks`: lista las tareas del usuario actual.
- `GET /tasks/{id}`: consulta una tarea del usuario actual.
- `POST /tasks`: valida y crea una tarea.
- `PUT /tasks/{id}`: valida y reemplaza toda la tarea (idempotente; el 409 ignora la propia tarea).
- `DELETE /tasks/{id}`: borra la tarea y responde 204 sin cuerpo; un segundo DELETE responde 404.

Los datos se guardan temporalmente en `tasks_data.json` para conservarlos entre peticiones del servidor integrado de PHP. El archivo se crea automáticamente y no forma parte del código de entrega.

En otra terminal, ejecuta la prueba proporcionada:

```bash
python3 probar_api.py
```
