# Changelog

Todos los cambios importantes de este proyecto se documentan en este archivo.

El formato se basa en [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) y el proyecto usa [Versionado Semántico](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-11-23

### Agregado

- CRUD de equipos con validación (422), inventario único (409) y formato de error único.
- Login con JWT (30 minutos) y contraseñas con bcrypt.
- Roles: solo el encargado da de alta, modifica o borra equipos (403).
- Préstamos con dueño: cada alumno ve y cancela solo los suyos (404 si no son suyos).
- Reportes de fallas clasificados por una IA externa, con tiempo máximo de espera y 3 intentos (502/503/504).
- Página web con CORS configurado por lista de orígenes.
- 28 pruebas automáticas en `probar_api.py`.

### Pendiente

- Cambiar SQLite por un servidor de base de datos si varias copias de la API corren al mismo tiempo.
