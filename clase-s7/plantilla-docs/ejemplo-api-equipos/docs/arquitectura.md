# Arquitectura

## Nivel 1 · Contexto

```mermaid
flowchart LR
    alumno["Alumno<br/>[Persona]<br/>Pide equipos y reporta fallas"]
    encargado["Encargado<br/>[Persona]<br/>Administra el inventario"]
    sistema["Préstamo de equipos<br/>[Sistema de software]"]
    ia["Gemini<br/>[Sistema externo]<br/>Clasifica los reportes por gravedad"]
    alumno -->|"Usa"| sistema
    encargado -->|"Usa"| sistema
    sistema -->|"Pide clasificar un reporte<br/>HTTPS + JSON"| ia
```

## Nivel 2 · Contenedores

```mermaid
flowchart LR
    usuario["Alumno o encargado<br/>[Persona]"]
    subgraph sistema["Préstamo de equipos"]
        front["Página web<br/>[Contenedor: HTML + JavaScript]<br/>Login, lista de equipos, alta y reportes"]
        api["API REST<br/>[Contenedor: Python · FastAPI]<br/>Reglas, JWT, roles y dueños"]
        bd[("Base de datos<br/>[Contenedor: SQLite]<br/>Equipos, usuarios, préstamos y reportes")]
    end
    ia["Gemini<br/>[Sistema externo]"]
    usuario -->|"Usa en el navegador"| front
    front -->|"fetch · HTTP + JSON<br/>Authorization: Bearer"| api
    api -->|"SQL con parámetros"| bd
    api -->|"HTTPS + JSON · timeout 2 s, 3 intentos"| ia
```

## Qué pasa si algo falla

| Si falla... | Qué responde la API | Por qué |
|---|---|---|
| Gemini tarda más de 2 segundos en los 3 intentos | 504 | La API no se queda esperando: avisa en segundos y el front muestra el mensaje. |
| Gemini responde un error o algo que no es JSON | 502 | El problema es del otro servicio, no de la petición del usuario. |
| Falta `GEMINI_API_KEY` | 503 | El servicio no está configurado; el resto de la API sigue funcionando. |
| La página viene de un origen que no está en la lista | El navegador bloquea | CORS protege al usuario de páginas ajenas. |
