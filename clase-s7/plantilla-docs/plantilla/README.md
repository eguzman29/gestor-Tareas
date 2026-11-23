# [COMPLETAR: nombre del proyecto] · API

[COMPLETAR: en 2 o 3 líneas, qué problema resuelve y para quién. Ejemplo: "API para que una clínica agende citas y los pacientes califiquen el servicio."]

- **Versión:** v1.0.0 (ver [CHANGELOG.md](CHANGELOG.md))
- **Lenguaje:** [COMPLETAR: Python · FastAPI / Node · Express / PHP]
- **Arquitectura:** [docs/arquitectura.md](docs/arquitectura.md)

## Cómo correrla

<!-- Tutorial: alguien que nunca vio su proyecto debe poder correrlo siguiendo estos pasos, en Windows y en cmd. -->

1. Clonar el repositorio:
   ```
   git clone [COMPLETAR: URL de su repositorio]
   cd [COMPLETAR: carpeta]
   ```
2. Instalar dependencias:
   ```
   [COMPLETAR: pip install -r requirements.txt  /  npm install  /  (PHP: nada)]
   ```
3. Configurar las variables de entorno (ver la tabla de abajo):
   ```
   [COMPLETAR: set JWT_SECRETO=...  (una línea por variable)]
   ```
4. Arrancar la API:
   ```
   [COMPLETAR: uvicorn main:app --reload  /  node index.js  /  php -S 127.0.0.1:8000 index.php]
   ```
5. Abrir el contrato en [COMPLETAR: http://127.0.0.1:8000/docs] y el front en [COMPLETAR: http://127.0.0.1:5500].

## Variables de entorno

Están todas en [.env.example](.env.example). **Ninguna clave real va en el repositorio.**

| Variable | Para qué sirve | Obligatoria |
|---|---|---|
| [COMPLETAR] | [COMPLETAR] | Sí / No |

## Ejemplos de uso

<!-- Guía práctica: 2 o 3 peticiones que muestren el uso típico. -->

Entrar y obtener el token:
```
[COMPLETAR: curl -X POST http://127.0.0.1:8000/login -H "Content-Type: application/json" -d "{...}"]
```

Usar una ruta protegida:
```
[COMPLETAR: curl http://127.0.0.1:8000/[recurso] -H "Authorization: Bearer %TOKEN%"]
```

## Contrato y errores

- Contrato OpenAPI: [COMPLETAR: /docs, o el enlace a openapi.yaml]
- Errores: todos responden `{"error": "...", "detalles": [...]}`. Ver [docs/errores.md](docs/errores.md).

## Cómo probarla

```
[COMPLETAR: python probar_api.py  /  la colección de Postman y cómo correrla]
```

Resultado de la versión v1.0.0: [COMPLETAR: por ejemplo, 28 de 28 pruebas]

## Anatomía de mi API

<!-- Peguen aquí lo que copia el botón "Copiar mi lista" del mapa interactivo. -->

```
[COMPLETAR: pegar su lista]
```

## REST y SOAP en mi proyecto

[COMPLETAR: un párrafo. ¿Qué servicio SOAP podría necesitar su proyecto (SAT, un banco, un proveedor de timbrado, IMSS...)? ¿En qué pieza de su API iría y qué haría para traducir de JSON a XML y de regreso?]

## Equipo y reflexiones

<!-- Cada integrante, con sus palabras. -->

| Integrante | Qué hizo |
|---|---|
| [COMPLETAR] | [COMPLETAR] |

**[COMPLETAR: nombre]:** [COMPLETAR: ¿qué pieza le costó más y por qué? ¿Qué haría distinto si este proyecto fuera de una empresa?]
