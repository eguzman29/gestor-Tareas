# Catálogo de errores

Todos los errores responden con el mismo formato:

```json
{"error": "Mensaje para el usuario", "detalles": ["detalle por campo, si aplica"]}
```

| Código | Cuándo | Ejemplo de mensaje |
|---|---|---|
| 401 | Falta el token, es falso o ya expiró | "El token ya expiró: inicia sesión otra vez" |
| 403 | Un alumno intenta lo que solo hace el encargado | "Tu rol (alumno) no puede hacer esto: solo el encargado" |
| 404 | El recurso no existe, o el préstamo no es tuyo | "No existe el préstamo 7" |
| 409 | Inventario repetido, equipo no disponible o borrar un equipo prestado | "No se puede borrar un equipo que está prestado" |
| 422 | Los datos no cumplen las reglas (tipo, formato, rango) | "Los datos no son válidos" + un detalle por campo |
| 502 | La IA respondió un error o algo inesperado | "La IA respondió en un formato inesperado" |
| 503 | Falta configurar la clave de la IA | "Falta configurar la variable de entorno GEMINI_API_KEY" |
| 504 | La IA no respondió a tiempo | "El servicio externo no respondió a tiempo (3 intentos de 2 s)" |
