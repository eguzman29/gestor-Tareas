# Arquitectura

<!-- Modelo C4 (Simon Brown): se dibuja por niveles, del más general al más detallado.
     Para su proyecto bastan el nivel 1 (contexto) y el nivel 2 (contenedores).
     Cada caja dice: nombre, [tipo y tecnología] y para qué sirve.
     Cada flecha dice: qué se pide y por qué medio (HTTP + JSON, SQL...). -->

## Nivel 1 · Contexto: quién usa el sistema y con qué otros sistemas habla

```mermaid
flowchart LR
    persona1["[COMPLETAR: tipo de usuario]<br/>[Persona]<br/>[COMPLETAR: qué hace]"]
    sistema["[COMPLETAR: nombre del sistema]<br/>[Sistema de software]"]
    externo["[COMPLETAR: servicio externo]<br/>[Sistema externo]<br/>[COMPLETAR: para qué lo usan]"]
    persona1 -->|"Usa"| sistema
    sistema -->|"[COMPLETAR: qué le pide]<br/>HTTPS + JSON"| externo
```

## Nivel 2 · Contenedores: las piezas que se ejecutan por separado

<!-- Un "contenedor" en C4 es cualquier cosa que corre por su cuenta: la página, la API, la base de datos.
     Aquí se ve lo DISTRIBUIDO de su proyecto: cada flecha es un mensaje por la red. -->

```mermaid
flowchart LR
    usuario["[COMPLETAR: usuario]<br/>[Persona]"]
    subgraph sistema["[COMPLETAR: nombre del sistema]"]
        front["Página web<br/>[Contenedor: HTML + JavaScript]<br/>[COMPLETAR: qué hace]"]
        api["API REST<br/>[Contenedor: COMPLETAR lenguaje]<br/>[COMPLETAR: qué hace]"]
        bd[("Base de datos<br/>[Contenedor: COMPLETAR motor]<br/>[COMPLETAR: qué guarda]")]
    end
    externo["[COMPLETAR: servicio externo]<br/>[Sistema externo]"]
    usuario -->|"Usa en el navegador"| front
    front -->|"fetch · HTTP + JSON<br/>Authorization: Bearer"| api
    api -->|"SQL con parámetros"| bd
    api -->|"HTTPS + JSON · timeout COMPLETAR s"| externo
```

## Qué pasa si algo falla

| Si falla... | Qué responde la API | Por qué |
|---|---|---|
| [COMPLETAR: el servicio externo tarda] | [COMPLETAR: 504] | [COMPLETAR] |
| [COMPLETAR: el servicio externo responde un error] | [COMPLETAR: 502] | [COMPLETAR] |
| [COMPLETAR: falta la clave del servicio] | [COMPLETAR: 503] | [COMPLETAR] |
