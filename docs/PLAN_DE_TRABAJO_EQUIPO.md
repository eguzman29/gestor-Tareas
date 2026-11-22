# Plan de Trabajo y Guía Paso a Paso para la Entrega Final (v1.0.0)
## Proyecto: Gestor de Tareas (API REST con PHP y SQLite)

Este documento es la **guía maestra** para completar todo lo que falta en el proyecto de acuerdo con las especificaciones de la clase (Sesión 7) y la rúbrica de evaluación (100 puntos).

El trabajo está dividido en **3 Bloques de Desarrollo independientes**, uno para cada integrante del equipo, permitiendo que cada miembro trabaje en su propia rama Git, registre sus propios commits y domine la pieza que le tocará defender en vivo el día de la presentación.

---

## Índice General

1. [Reglas del Flujo Git (Opción A: Ramas y Tag v1.0.0)](#1-reglas-del-flujo-git)
2. [Fase 0: Sincronización Inicial del Repositorio](#2-fase-0-sincronización-inicial-del-repositorio)
3. [Bloque 1 (Integrante 1): Arquitectura C4, Decisiones Técnicas (ADR) y Resiliencia](#3-bloque-1-integrante-1-arquitectura-c4-decisiones-técnicas-adr-y-resiliencia)
4. [Bloque 2 (Integrante 2): La Puerta (Seguridad, CORS, JWT), Contrato y Catálogo de Errores](#4-bloque-2-integrante-2-la-puerta-seguridad-cors-jwt-contrato-y-catálogo-de-errores)
5. [Bloque 3 (Integrante 3): README Final, Integración REST vs SOAP y Changelog v1.0.0](#5-bloque-3-integrante-3-readme-final-integración-rest-vs-soap-y-changelog-v100)
6. [Fase Final: Integración, Pruebas y Creación del Tag v1.0.0](#6-fase-final-integración-pruebas-y-creación-del-tag-v100)
7. [Guion de Presentación y Defensa Técnica (10 Minutos)](#7-guion-de-presentación-y-defensa-técnica-10-minutos)

---

## 1. Reglas del Flujo Git

1. **Nunca trabajen directo sobre `main`:** Cada integrante creará su propia rama con el nombre de su bloque.
2. **Identidad de autor:** Antes de hacer commit, cada integrante debe asegurarse de que Git tenga su nombre y correo:
   ```bash
   git config user.name "Tu Nombre Completo"
   git config user.email "tu_correo@ejemplo.com"
   ```
3. **`v1.0.0` no es una rama:** Es una **etiqueta (tag)** que se coloca al final sobre `main` cuando los 3 bloques ya estén fusionados y probados.

---

## 2. Fase 0: Sincronización Inicial del Repositorio
*(Esta fase la realiza un solo integrante, ej. Ethan / Líder, antes de que los demás creen sus ramas)*

Actualmente, el trabajo funcional más reciente está en la rama `cors-y-front`. Debemos integrarlo a `main` para que sea el punto de partida oficial de todos:

```bash
# 1. Asegurar que .env y la carpeta de material de clase no ensucien el repo
# Verificar que en .gitignore estén estas líneas:
# tasks.sqlite
# .env
# clase-s7/

# 2. Cambiar a la rama main y traer los cambios de cors-y-front
git checkout main
git merge cors-y-front -m "Merge branch 'cors-y-front' into main"

# 3. Subir el main actualizado al repositorio remoto
git push origin main
```

A partir de este momento, todos los integrantes hacen `git pull origin main` y crean sus ramas.

---

## 3. Bloque 1 (Integrante 1): Arquitectura C4, Decisiones Técnicas (ADR) y Resiliencia

* **Responsable:** Integrante 1
* **Rama de trabajo:** `feature/bloque-arquitectura`
* **Piezas del mapa que defenderá en la presentación:**
  * Base de datos (SQLite, persistencia y sentencias preparadas contra inyección SQL).
  * Integración externa (Gemini API, cURL, manejo de timeouts de 5s, códigos 502/504).
  * Modelo C4 de arquitectura distribuida.

### Paso a paso para el Integrante 1:

#### Paso 1.1: Crear su rama
```bash
git checkout main
git pull origin main
git checkout -b feature/bloque-arquitectura
```

#### Paso 1.2: Crear las carpetas de documentación
```bash
mkdir -p docs/decisiones
```

#### Paso 1.3: Crear el archivo `docs/arquitectura.md`
Crear el archivo `docs/arquitectura.md` con el siguiente contenido (diagramas Mermaid C4 y tabla de fallas):

```markdown
# Arquitectura del Sistema

Este documento describe la arquitectura del **Gestor de Tareas** siguiendo el **Modelo C4** (Contexto y Contenedores) para visualizar los límites del sistema y la comunicación distribuida entre sus componentes.

---

## Nivel 1 · Diagrama de Contexto del Sistema

Muestra a los usuarios que interactúan con el sistema y los servicios externos conectados.

\`\`\`mermaid
flowchart LR
    usuario["Usuario del Sistema<br/>[Persona]<br/>Gestiona tareas diarias y solicita análisis inteligente"]
    sistema["Gestor de Tareas API<br/>[Sistema de Software]<br/>Provee servicios REST para autenticación, CRUD y reportes"]
    gemini["Google Gemini API<br/>[Sistema Externo]<br/>Modelo de lenguaje que analiza descripciones y sugiere prioridades"]

    usuario -->|"Usa la aplicación web<br/>HTTPS"| sistema
    sistema -->|"Envía descripciones para análisis<br/>HTTPS + JSON"| gemini
\`\`\`

---

## Nivel 2 · Diagrama de Contenedores

Muestra las aplicaciones, servicios y bases de datos que componen el sistema, destacando los protocolos de red y comunicación entre ellos.

\`\`\`mermaid
flowchart LR
    usuario["Usuario<br/>[Persona]"]

    subgraph sistema["Sistema Gestor de Tareas"]
        front["Página Web (Frontend)<br/>[Contenedor: HTML5 / JavaScript / CSS]<br/>Interfaz para login, gestión de tareas y visualización de reportes"]
        api["API REST (Backend)<br/>[Contenedor: PHP 8.1+ sin framework]<br/>Control de acceso JWT, enrutador, reglas de negocio y validación"]
        bd[("Base de Datos Relacional<br/>[Contenedor: SQLite - tasks.sqlite]<br/>Almacena usuarios, credenciales y tareas por usuario")]
    end

    gemini["Gemini API (Google AI Studio)<br/>[Servicio Externo]<br/>Endpoint de generación de contenido (gemini-flash-lite)"]

    usuario -->|"Interactúa en el navegador"| front
    front -->|"Peticiones HTTP/JSON<br/>Headers: Authorization Bearer, Origin"| api
    api -->|"Consultas SQL parametrizadas (PDO)"| bd
    api -->|"Llamadas cURL (Timeout 5s, 2 intentos)<br/>HTTPS + JSON"| gemini
\`\`\`

---

## Manejo de Fallas en la Comunicación Distribuida

En un sistema distribuido, las dependencias de red pueden fallar o demorarse. La API implementa las siguientes respuestas ante fallos:

| Evento de Falla | Código HTTP | Diagnóstico y Acción de la API |
|---|:---:|---|
| **La IA externa tarda más de 5 segundos** | `504 Gateway Timeout` | cURL interrumpe la petición mediante `CURLOPT_TIMEOUT => 5` y devuelve un JSON claro informando que el servicio tardó demasiado. |
| **Falla de conexión de red o error 5xx de Gemini** | `502 Bad Gateway` | La API detecta error en la capa de transporte o respuesta defectuosa del proveedor externo. |
| **No está configurada `GEMINI_API_KEY`** | `503 Service Unavailable` | La API avisa que la funcionalidad de IA no está disponible en el servidor, pero el resto del CRUD sigue operativo. |
| **Petición desde un dominio no autorizado (CORS)** | Bloqueo Navegador / `403` | La API valida el encabezado `Origin` contra la lista blanca permitida. Preflights `OPTIONS` devuelven `204 No Content` si es válido o `403` si no lo es. |
```

#### Paso 1.4: Crear el primer ADR (`docs/decisiones/0001-uso-de-sqlite-y-sentencias-preparadas.md`)
```markdown
# 1. Uso de SQLite con Sentencias Preparadas (PDO)

- **Fecha:** 2026-11-23
- **Estado:** Aceptada

## Contexto
El sistema necesita persistir usuarios y tareas de forma confiable, garantizando aislamiento de datos entre usuarios y protección contra ataques comunes como inyección SQL, sin la sobrecarga operativa de montar un servidor de base de datos externo para esta etapa.

## Decisión
Se seleccionó **SQLite** gestionado a través de **PDO (PHP Data Objects)**.
Todas las operaciones que involucren parámetros del cliente se ejecutan obligatoriamente mediante sentencias preparadas (`$db->prepare()` con marcadores de posición `?`).
El aislamiento multiusuario se asegura mediante cláusulas `WHERE usuario_id = ?` en todas las consultas de selección, actualización y borrado.

## Consecuencias
- **Positivas:** Despliegue inmediato sin dependencias de red externas para la BD; protección nativa contra inyecciones SQL; soporte ACID local.
- **Negativas / Limitaciones:** Concurrencia limitada de escritura por bloqueo a nivel de archivo si la API escalara a múltiples instancias en clúster.
```

#### Paso 1.5: Crear el segundo ADR (`docs/decisiones/0002-timeout-y-resiliencia-ia.md`)
```markdown
# 2. Control de Tiempos Máximos (Timeout) y Resiliencia con Servicios Externos

- **Fecha:** 2026-11-23
- **Estado:** Aceptada

## Contexto
La función de generación de reportes y estimación de prioridades depende de un proveedor externo de Inteligencia Artificial (Google Gemini). Si el proveedor sufre lentitud o caídas, la API no debe congelarse indefinidamente esperando respuesta, lo que degradaría los hilos de ejecución del servidor PHP.

## Decisión
Se implementó un cliente cURL con:
1. `CURLOPT_TIMEOUT => 5` (tiempo máximo total de 5 segundos).
2. Mecanismo de reintento automático (hasta 2 intentos) exclusivamente ante códigos transitorios `429` (límite de cuota) o `503`.
3. Mapeo estricto de códigos de falla: `504 Gateway Timeout` ante expiración de tiempo, `502 Bad Gateway` ante respuesta corrupta o error del servidor externo, y `503 Service Unavailable` si falta la clave de entorno.

## Consecuencias
- **Positivas:** El cliente recibe respuesta en un tiempo delimitado y predecible; no se satura el pool de conexiones de la API.
- **Negativas:** Si la latencia de internet es superior a 5 segundos, la petición se cancela requiriendo un reintento manual del usuario.
```

#### Paso 1.6: Subir sus cambios a GitHub
```bash
git add docs/
git commit -m "docs: agrega arquitectura C4 (Mermaid) y decisiones tecnicas ADR 0001 y 0002"
git push origin feature/bloque-arquitectura
```

---

## 4. Bloque 2 (Integrante 2): La Puerta (Seguridad, CORS, JWT), Contrato y Catálogo de Errores

* **Responsable:** Integrante 2
* **Rama de trabajo:** `feature/bloque-seguridad-y-errores`
* **Piezas del mapa que defenderá en la presentación:**
  * "La Puerta": Validación de cabeceras CORS (`Access-Control-Allow-Origin`, Preflight `OPTIONS`).
  * Autenticación con JWT (`firmar_jwt`, `verificar_jwt`, Bearer token, expiración de 1800s).
  * Manejo de códigos de estado HTTP y catálogo de errores (401, 403, 404, 409, 422).

### Paso a paso para el Integrante 2:

#### Paso 2.1: Crear su rama
```bash
git checkout main
git pull origin main
git checkout -b feature/bloque-seguridad-y-errores
```

#### Paso 2.2: Crear el archivo `.env.example` en la raíz del proyecto
Crear el archivo `.env.example`:
```ini
# Configuración del Entorno - Gestor de Tareas API
# Copia este archivo a .env y asigna tus valores locales (NUNCA subas .env al repositorio)

# Clave de API de Google Gemini (AI Studio)
GEMINI_API_KEY=tu_clave_de_gemini_aqui

# Modelo de lenguaje utilizado para análisis de tareas
GEMINI_MODELO=gemini-flash-lite-latest

# URL del servicio externo (omitir o dejar en blanco para usar la API oficial de Google)
# Para pruebas locales con el simulador: http://127.0.0.1:9000
EXTERNO_URL=

# Secreto para firma y validación de tokens JWT (HS256)
JWT_SECRETO=clave_secreta_super_segura_para_firmar_tokens

# Origen permitido para CORS (puerto del frontend local)
CORS_ORIGEN_PERMITIDO=http://127.0.0.1:5500
```

#### Paso 2.3: Crear el archivo `docs/errores.md`
Crear `docs/errores.md`:
```markdown
# Catálogo de Respuestas y Códigos de Error

La API del **Gestor de Tareas** estandariza todas sus respuestas de error bajo un formato JSON único y predecible:

\`\`\`json
{
  "error": "Mensaje descriptivo del error para el cliente",
  "detalles": [
    "campo: explicación detallada del fallo"
  ]
}
\`\`\`

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
```

#### Paso 2.4: Verificar el Contrato de la API (`openapi.yaml` y `/docs`)
Verificar que el archivo `openapi.yaml` existente en la raíz describa las rutas `/tasks`, `/login` y `/reportes`. La API sirve Swagger UI automáticamente en `http://127.0.0.1:8000/docs`.

#### Paso 2.5: Subir sus cambios a GitHub
```bash
git add .env.example docs/errores.md
git commit -m "docs: agrega plantilla .env.example y catalogo formal de errores docs/errores.md"
git push origin feature/bloque-seguridad-y-errores
```

---

## 5. Bloque 3 (Integrante 3): README Final, Integración REST vs SOAP y Changelog v1.0.0

* **Responsable:** Integrante 3
* **Rama de trabajo:** `feature/bloque-readme-soap-changelog`
* **Piezas del mapa que defenderá en la presentación:**
  * Introducción (1 minuto): Qué problema resuelve la API del Gestor de Tareas.
  * Reglas de negocio (validación 422, duplicados 409, estados).
  * Explicación de la integración teórica REST vs SOAP.

### Paso a paso para el Integrante 3:

#### Paso 3.1: Crear su rama
```bash
git checkout main
git pull origin main
git checkout -b feature/bloque-readme-soap-changelog
```

#### Paso 3.2: Crear el archivo `CHANGELOG.md`
Crear `CHANGELOG.md` en la raíz del proyecto:
```markdown
# Changelog

Todos los cambios notables de este proyecto están documentados en este archivo.

El formato se basa en [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
y este proyecto se adhiere a [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.0.0] - 2026-11-23

### Agregado
- **CRUD completo de tareas:** Endpoints `GET`, `POST`, `PUT`, `DELETE` sobre `/tasks` con códigos de estado semánticos (200, 201, 204, 404, 409, 422).
- **Autenticación y Seguridad:** Endpoint `POST /login` con generación y validación de tokens JWT (HS256, expiración a 30 minutos).
- **Aislamiento multiusuario:** Cada usuario solo puede consultar, modificar o eliminar sus propias tareas en SQLite.
- **Control de Duplicados:** Regla de negocio que responde `409 Conflict` si se intenta registrar una tarea con idéntico título y fecha límite.
- **Integración externa con IA:** Endpoint `POST /reportes` que interactúa con Google Gemini (o simulador de fallos) mediante cURL, con timeout de 5s y reintentos automáticos (502, 503, 504).
- **CORS y Frontend:** Soporte para preflights `OPTIONS` y encabezados `Access-Control-Allow-Origin` para conectar la interfaz web en `front/index.html`.
- **Contrato OpenAPI y Documentación:** Interfaz Swagger UI servida en `/docs`, diagramas C4 en `docs/arquitectura.md`, catálogo en `docs/errores.md` y 2 decisiones de arquitectura (ADR).
- **Batería de pruebas automatizadas:** Scripts de verificación `probar_api.py` y `probar_ia.py` con 100% de casos aprobados.

### Cambiado
- Migración del almacenamiento de archivos JSON planos a una base de datos relacional SQLite (`tasks.sqlite`) mediante PDO con sentencias preparadas.
```

#### Paso 3.3: Actualizar el archivo `README.md`
Actualizar el archivo `README.md` de la raíz del proyecto con el contenido estructurado que exige la plantilla oficial:

```markdown
# Gestor de Tareas · API REST

API REST desarrollada en **PHP 8.1+ puro (sin frameworks)** para la gestión organizada de tareas personales y análisis inteligente de pendientes mediante Inteligencia Artificial (Gemini).

- **Versión:** v1.0.0 (ver [CHANGELOG.md](CHANGELOG.md))
- **Lenguaje:** PHP 8.1+ (PDO SQLite, cURL)
- **Contrato:** [openapi.yaml](openapi.yaml) / Servido en `/docs`
- **Arquitectura:** [docs/arquitectura.md](docs/arquitectura.md)
- **Catálogo de Errores:** [docs/errores.md](docs/errores.md)

---

## Cómo correrla en pocos pasos

### 1. Requisitos previos
- PHP 8.1 o superior con extensiones SQLite y cURL (`sudo apt install php-sqlite3 php-curl`).
- Python 3 para ejecutar las pruebas automáticas.

### 2. Configuración de variables
Copia la plantilla de entorno:
\`\`\`bash
cp .env.example .env
\`\`\`
Define tus variables en la terminal donde correrás la API:
\`\`\`bash
export GEMINI_API_KEY="tu_clave_de_google_ai_studio"
export GEMINI_MODELO="gemini-flash-lite-latest"
\`\`\`

### 3. Arrancar la API
\`\`\`bash
php -S 127.0.0.1:8000 api.php
\`\`\`

### 4. Abrir la documentación y la interfaz web
- **Swagger UI (Contrato interactivo):** http://127.0.0.1:8000/docs
- **Página Frontend:** Abre el archivo `front/index.html` en tu navegador o mediante un servidor local (ej. Live Server en http://127.0.0.1:5500).

---

## Variables de Entorno

Detalladas en [.env.example](.env.example):

| Variable | Descripción | Obligatoria |
|---|---|:---:|
| `GEMINI_API_KEY` | Clave secreta para Google AI Studio | Sí (para `/reportes`) |
| `GEMINI_MODELO` | Modelo de IA (`gemini-flash-lite-latest`) | No (usa default) |
| `EXTERNO_URL` | URL base para pruebas o simulador local | No |
| `JWT_SECRETO` | Clave secreta para firma de tokens HMAC-SHA256 | No (usa valor base) |

---

## Ejemplos de uso con cURL

### 1. Iniciar sesión (obtener token JWT)
\`\`\`bash
curl -X POST http://127.0.0.1:8000/login \
  -H "Content-Type: application/json" \
  -d '{"usuario": "juan", "clave": "secreta123"}'
\`\`\`

### 2. Listar tareas del usuario autenticado
\`\`\`bash
curl -X GET http://127.0.0.1:8000/tasks \
  -H "Authorization: Bearer <TU_TOKEN_JWT>"
\`\`\`

### 3. Crear una nueva tarea
\`\`\`bash
curl -X POST http://127.0.0.1:8000/tasks \
  -H "Authorization: Bearer <TU_TOKEN_JWT>" \
  -H "Content-Type: application/json" \
  -d '{"titulo": "Estudiar para el examen", "prioridad": "alta", "fecha_limite": "2026-11-25"}'
\`\`\`

---

## Cómo probarla

En una terminal independiente con la API corriendo:
\`\`\`bash
python3 probar_api.py
\`\`\`
Para verificar la resiliencia y pruebas de fallo del servicio externo:
\`\`\`bash
python3 probar_ia.py
\`\`\`

---

## Anatomía de mi API

*(Lista generada desde `clase-s7/mapa_api.html`)*

```text
Anatomía de mi API · lista de revisión
0 · Cliente
  [x] Mi proyecto tiene un cliente que consume la API: página web en front/index.html y scripts en Python.
1 · Petición
  [x] La petición viaja por HTTP con método, ruta, encabezados (Content-Type, Authorization) y cuerpo JSON.
2 · La puerta
  [x] CORS configurado para admitir el origen del frontend con preflight OPTIONS.
  [x] Autenticación con Bearer token JWT (401 si falta o es inválido).
  [x] Validación sintáctica de JSON (400) y de esquema de datos (422).
3 · Endpoint y Enrutador
  [x] Enrutamiento por método y URI en api.php.
4 · Regla de negocio
  [x] Control de tareas duplicadas por título y fecha límite para el mismo usuario (409 Conflict).
  [x] Aislamiento de tareas por usuario autenticado.
5 · Integración externa
  [x] Cliente cURL hacia Google Gemini con control de timeout (5s) y códigos de error (502, 503, 504).
6 · Base de datos
  [x] Persistencia en SQLite mediante sentencias preparadas con PDO.
7 · Respuesta
  [x] Códigos HTTP semánticos (200, 201, 204, 401, 404, 409, 422, 502, 504) y formato JSON estándar.
```

---

## REST y SOAP en mi proyecto

Si este Gestor de Tareas se implementara en un entorno corporativo o gubernamental, requeriría interactuar con servicios heredados (**SOAP**), como por ejemplo el sistema de timbrado y facturación de servicios del **SAT** o un directorio empresarial centralizado de empleados (Active Directory / LDAP bancario). 

En nuestra arquitectura, el cliente web seguiría comunicándose mediante **REST y JSON**. El servicio SOAP se ubicaría internamente en la **pieza de integración externa (7b)**: la API REST recibiría la petición JSON del cliente, un módulo adaptador construiría el sobre XML (`soapenv:Envelope` con el cuerpo `soapenv:Body`), ejecutaría una petición `POST` con la cabecera `SOAPAction` y un tiempo límite de espera estricto (timeout), parsearía la respuesta XML resultante extrayendo los nodos pertinentes (ej. `<Estado>Vigente</Estado>`), y respondería al frontend un objeto JSON limpio (`{"estado": "Vigente"}`). De este modo, la complejidad y el formato XML de SOAP quedan completamente encapsulados en el servidor, sin exponerlos al cliente.

---

## Equipo y Reflexiones

| Integrante | Rol y Pieza Principal |
|---|---|
| **[Nombre Integrante 1]** | Arquitectura C4, persistencia en SQLite y resiliencia de servicios externos (ADR). |
| **[Nombre Integrante 2]** | Seguridad ("La Puerta"), CORS, validación JWT, contrato y catálogo de errores. |
| **[Nombre Integrante 3]** | Documentación general, diseño de integración REST/SOAP, reglas de negocio y Changelog. |

### Reflexiones Individuales

* **[Nombre Integrante 1]:** *"La pieza que más me costó comprender y afinar fue la gestión de fallas del servicio externo con cURL, asegurando que los timeouts respondieran oportunamente un 504 en vez de congelar la API. En un entorno empresarial real, implementaría un patrón Circuit Breaker y una cola de mensajería (RabbitMQ o Redis) para procesar tareas pesadas de IA de forma asíncrona."*
* **[Nombre Integrante 2]:** *"Lo más desafiante fue la correcta verificación de firmas JWT y la prevención de fugas de datos asegurando que cada consulta SQL filtre obligatoriamente por el ID del usuario autenticado. En una empresa, migraría a un servidor OAuth2 / OIDC dedicado (como Keycloak o Auth0) con rotación de claves RSA/Ed25519 en vez de secreto compartido."*
* **[Nombre Integrante 3]:** *"La mayor dificultad fue armonizar todas las reglas de negocio y códigos de estado (distinguir con precisión cuándo responder 422 vs 409 vs 404) y estandarizar el contrato OpenAPI. En un proyecto empresarial, adoptaría una metodología API-First con pruebas de contrato automatizadas integradas en un pipeline de CI/CD."*
```

*(Nota: Los nombres entre corchetes deben reemplazarse con los nombres reales de los 3 integrantes).*

#### Paso 3.4: Subir sus cambios a GitHub
```bash
git add README.md CHANGELOG.md
git commit -m "docs: completa README con seccion REST/SOAP, anatomia, reflexiones y agrega CHANGELOG v1.0.0"
git push origin feature/bloque-readme-soap-changelog
```

---

## 6. Fase Final: Integración, Pruebas y Creación del Tag v1.0.0

Una vez que los 3 integrantes hayan subido sus respectivas ramas a GitHub:

### Paso 6.1: Integrar todas las ramas a `main`
El líder del equipo (o mediante Pull Requests en GitHub) fusiona las 3 ramas en `main`:

```bash
git checkout main
git pull origin main

# Fusionar bloque 1
git merge feature/bloque-arquitectura -m "Merge branch 'feature/bloque-arquitectura' into main"

# Fusionar bloque 2
git merge feature/bloque-seguridad-y-errores -m "Merge branch 'feature/bloque-seguridad-y-errores' into main"

# Fusionar bloque 3
git merge feature/bloque-readme-soap-changelog -m "Merge branch 'feature/bloque-readme-soap-changelog' into main"
```

### Paso 6.2: Ejecutar la verificación final
Con PHP corriendo en una terminal (`php -S 127.0.0.1:8000 api.php`), ejecutar en otra terminal:
```bash
python3 probar_api.py
```
Todas las pruebas deben concluir en verde con resultado exitoso.

### Paso 6.3: Crear y subir la etiqueta `v1.0.0`
```bash
# 1. Subir main con todos los merges
git push origin main

# 2. Crear la etiqueta oficial de calificación
git tag v1.0.0

# 3. Subir la etiqueta a GitHub
git push origin v1.0.0
```

---

## 7. Guion de Presentación y Defensa Técnica (10 Minutos)

Para la exposición del martes (sin diapositivas, con código corriendo):

### Minuto 0 a 1: Introducción (Integrante 3)
* Explica qué problema resuelve el **Gestor de Tareas**: organización eficiente de actividades con fechas límite, estados de progreso y categorización asistida por IA.

### Minuto 1 a 7: Recorrido en vivo de la Anatomía de la API
* **Integrante 2 (La Puerta y Seguridad - 2 min):**
  * Muestra la petición de login en vivo y la obtención del token Bearer.
  * Muestra cómo la API responde `401` si se envía un token alterado o ausente.
  * Muestra el manejo de CORS para que el frontend (`front/index.html`) pueda consultar el backend sin bloqueos.
  * Muestra una respuesta `422` enviando un campo vacío.
* **Integrante 3 (Reglas de Negocio y Datos - 2 min):**
  * Muestra la creación de una tarea y cómo responde `409 Conflict` si se intenta crear otra idéntica.
  * Muestra que el usuario solo puede ver y editar sus propias tareas (`WHERE usuario_id = ?`).
* **Integrante 1 (Integración Externa y Arquitectura - 2 min):**
  * Muestra el endpoint `/reportes` llamando a la IA para clasificar una tarea.
  * Muestra el diagrama C4 en `docs/arquitectura.md`.
  * Explica qué pasa si Gemini falla o tarda más de 5 segundos (`504 Gateway Timeout` controlado por cURL).
* **Cierre del recorrido (30 seg):**
  * Mostrar la terminal corriendo `python3 probar_api.py` con todas las pruebas pasando.

### Minutos 7 a 10: Sesión de Preguntas del Profesor (Cualquiera responde)
Tener bien ubicadas las siguientes líneas de `api.php`:
1. *¿Qué línea decide el 401 por token inválido?* $\rightarrow$ Línea 237 (`error_api(401, 'El token no es válido o ya expiró.')`).
2. *¿Cuál consulta protege el aislamiento entre usuarios?* $\rightarrow$ Líneas 264 y 284 (`WHERE usuario_id = ?`).
3. *¿Dónde está configurada la clave externa y el timeout?* $\rightarrow$ Líneas 94 (`GEMINI_API_KEY`) y 111 (`CURLOPT_TIMEOUT => 5`).
4. *¿Por qué 422 y no 400?* $\rightarrow$ 400 es JSON mal formado; 422 es JSON bien formado pero datos que no cumplen las reglas de negocio.
5. *¿Cómo integrarían SOAP?* $\rightarrow$ En la pieza 7b (integración externa), usando un sobre XML con cURL y traduciendo la respuesta a JSON para el frontend.
