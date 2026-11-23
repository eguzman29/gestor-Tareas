# Plantilla de documentación para su API

Así documentan su API los equipos profesionales: **dentro del repositorio, junto al código**, en archivos Markdown que GitHub muestra solo.

## Qué hay en esta carpeta

- `plantilla/`: los archivos para copiar a su repositorio. Reemplacen cada `[COMPLETAR: ...]`.
- `ejemplo-api-equipos/`: los mismos archivos ya llenos con la API de préstamo de equipos de la clase. Úsenlo para ver cómo queda, no para copiarlo.

## Qué copiar y dónde

```
su-repositorio/
├── README.md            ← OBLIGATORIO · cómo correrla, configurarla y probarla
├── CHANGELOG.md         ← OBLIGATORIO · qué trae la versión v1.0.0
├── .env.example         ← OBLIGATORIO · las variables de entorno, SIN valores secretos
├── (su contrato)        ← OBLIGATORIO · /docs en FastAPI, u openapi.yaml en Node o PHP
└── docs/
    ├── arquitectura.md  ← OBLIGATORIO · el diagrama de su sistema (modelo C4)
    ├── errores.md       ← recomendado · los códigos que responde su API
    └── decisiones/      ← recomendado · 2 decisiones explicadas (ADR)
```

## Por qué así: los 4 tipos de documentación (Diátaxis)

| Tipo | Pregunta que responde | Archivo |
|---|---|---|
| Tutorial | ¿Cómo la pongo a correr? | `README.md`, sección "Cómo correrla" |
| Guía práctica | ¿Cómo hago algo concreto? | `README.md`, sección "Ejemplos de uso" |
| Referencia | ¿Qué recibe y qué responde? | El contrato OpenAPI y `docs/errores.md` |
| Explicación | ¿Por qué está hecha así? | `docs/arquitectura.md` y `docs/decisiones/` |

## Reglas

1. **Nunca suban su archivo `.env` real.** Agreguen `.env` a su `.gitignore`. Lo que se sube es `.env.example`, con los nombres de las variables y sin las claves.
2. **Los diagramas van en Mermaid**, dentro del Markdown: GitHub los dibuja solos. Para ver cómo queda antes de subirlo: https://mermaid.live
3. **Escríbanlo ustedes.** Si usan IA para redactar, revisen que lo que dice sea cierto en su código: el martes se los voy a preguntar.

## Referencias

- Procida, D. (s.f.). *Diátaxis: A systematic approach to technical documentation authoring*. https://diataxis.fr/
- Brown, S. (s.f.). *The C4 model for visualising software architecture*. https://c4model.com/
- Nygard, M. (2011, 15 de noviembre). *Documenting architecture decisions*. Cognitect. https://cognitect.com/blog/2011/11/15/documenting-architecture-decisions
- Lacan, O. (s.f.). *Keep a Changelog* (versión 1.1.0). https://keepachangelog.com/en/1.1.0/
- Preston-Werner, T. (s.f.). *Semantic Versioning 2.0.0*. https://semver.org/spec/v2.0.0.html
- OpenAPI Initiative. (2025). *OpenAPI Specification v3.2.0*. https://spec.openapis.org/oas/latest.html
