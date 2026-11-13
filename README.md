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
- `GET /docs`: sirve el contrato `openapi.yaml`.
- `POST /reportes`: analiza una descripción mediante Gemini y devuelve un resumen y prioridad.

Los datos se guardan en `tasks.sqlite`, una base SQLite creada automáticamente mediante PDO. Las consultas usan parámetros preparados (`?`) y el archivo local está excluido del repositorio.

En otra terminal, ejecuta la prueba proporcionada:

```bash
python3 probar_api.py
```

## Sesión 5: endpoint con IA

Instala cURL para PHP si `php -m | grep curl` no muestra nada:

```bash
sudo apt install php8.3-curl
```

Configura la clave solo en la terminal donde arrancarás PHP; nunca la escribas en el código ni la subas a GitHub:

```bash
export GEMINI_API_KEY="tu-clave-de-AI-Studio"
export GEMINI_MODELO="gemini-flash-lite-latest"
php -S 127.0.0.1:8010 api.php
```

Prueba el endpoint real con el bloque `externo` de `prueba.json`:

```bash
python3 probar_api.py
```

Para probar fallos controlados con el simulador del kit:

```bash
export EXTERNO_URL=http://127.0.0.1:9000
python3 simulador.py
```

En otra terminal, con PHP arrancado después de definir `EXTERNO_URL`:

```bash
python3 probar_ia.py
```

La prueba rápida debe terminar con `RESULTADO: 4 de 4`. La prueba de tarea debe pasar los casos 21, 22 y 25.
