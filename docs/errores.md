# Catálogo de Respuestas y Códigos de Error

La API del **Gestor de Tareas** estandariza todas sus respuestas de error bajo un formato JSON único y predecible:

```json
{
  "error": "Mensaje descriptivo del error para el cliente",
  "detalles": [
    "campo: explicación detallada del fallo"
  ]
}
```

---

## Tabla de Códigos de Estado HTTP

| Código | Nombre | Cuándo se produce | Ejemplo de respuesta |
|:---:|---|---|---|
| **400** | Bad Request | El cuerpo de la petición no es un JSON bien formado o sintácticamente válido. | `{"error": "El cuerpo debe ser un JSON válido.", "detalles": []}` |
| **401** | Unauthorized | Falta la cabecera `Authorization`, no usa el esquema `Bearer`, o el token JWT expiró o fue alterado. | `{"error": "El token no es válido o ya expiró.", "detalles": ["token: inválido"]}` |
| **403** | Forbidden | Intento de acceso desde un origen web no autorizado por la política de CORS. | Responde código 403 en preflight OPTIONS. |
| **404** | Not Found | La tarea con el ID indicado no existe o pertenece a otro usuario (aislamiento de datos). | `{"error": "La tarea no existe.", "detalles": []}` |
| **409** | Conflict | Regla de negocio de duplicados: ya existe una tarea con el mismo título y fecha límite para ese usuario. | `{"error": "Ya tienes una tarea con el mismo título y fecha.", "detalles": []}` |
| **422** | Unprocessable Entity | Los campos requeridos no cumplen con el tipo, longitud o formato de fecha esperado (`YYYY-MM-DD`). | `{"error": "Datos inválidos.", "detalles": ["titulo: es obligatorio", "prioridad: debe ser baja, media o alta"]}` |
| **502** | Bad Gateway | El servicio de IA devolvió un error de servidor o un payload que no es JSON válido. | `{"error": "No se pudo conectar con la IA.", "detalles": []}` |
| **503** | Service Unavailable | El servidor no tiene configurada la variable `GEMINI_API_KEY` o falta la extensión `cURL`. | `{"error": "Falta configurar la variable GEMINI_API_KEY.", "detalles": []}` |
| **504** | Gateway Timeout | La llamada cURL al servicio externo superó el tiempo máximo de 5 segundos. | `{"error": "La IA tardó demasiado en responder.", "detalles": []}` |

---

## Códigos de Éxito

| Código | Nombre | Uso |
|:---:|---|---|
| **200** | OK | Peticiones `GET /tasks`, `GET /tasks/{id}`, `POST /login` y `POST /reportes`. |
| **201** | Created | Petición `POST /tasks` exitosa con el recurso recién creado en el cuerpo. |
| **204** | No Content | Petición `DELETE /tasks/{id}` exitosa (sin contenido en el cuerpo). |