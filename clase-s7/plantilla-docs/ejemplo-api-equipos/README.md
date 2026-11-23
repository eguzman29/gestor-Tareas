# Préstamo de equipos · API

API para que un laboratorio preste equipos (laptops, proyectores, cámaras) a sus alumnos. El encargado administra el inventario; cada alumno ve y cancela solo sus propios préstamos y puede reportar fallas, que una IA clasifica por gravedad.

- **Versión:** v1.0.0 (ver [CHANGELOG.md](CHANGELOG.md))
- **Lenguaje:** Python · FastAPI
- **Arquitectura:** [docs/arquitectura.md](docs/arquitectura.md)

## Cómo correrla

1. Clonar el repositorio:
   ```
   git clone https://github.com/ejemplo/api-equipos.git
   cd api-equipos
   ```
2. Instalar dependencias:
   ```
   pip install fastapi uvicorn pyjwt bcrypt
   ```
3. Configurar las variables de entorno (ver la tabla de abajo):
   ```
   set JWT_SECRETO=una-cadena-larga-y-aleatoria
   set GEMINI_API_KEY=su-clave
   ```
4. Arrancar la API:
   ```
   uvicorn api_equipos:app --reload
   ```
5. Abrir el contrato en http://127.0.0.1:8000/docs y el front en http://127.0.0.1:5500 (`cd front` y `python -m http.server 5500`).

Usuarios de prueba: `encargado / encargado123` (rol encargado), `ana / ana12345` y `beto / beto12345` (rol alumno).

## Variables de entorno

Están todas en [.env.example](.env.example). **Ninguna clave real va en el repositorio.**

| Variable | Para qué sirve | Obligatoria |
|---|---|---|
| `JWT_SECRETO` | Firmar los tokens. Sin ella usa una de desarrollo que no debe llegar a producción. | Sí, en producción |
| `GEMINI_API_KEY` | La clave de la IA que clasifica los reportes. Sin ella, `POST /reportes` responde 503. | Para reportes |
| `GEMINI_MODELO` | El modelo de Gemini que se usa. | No |
| `EXTERNO_URL` | A dónde se manda la petición de IA. En clase apunta al simulador. | No |
| `EXTERNO_TIMEOUT` | Segundos máximos de espera por intento (2 por omisión). | No |
| `CORS_ORIGENES` | Páginas que pueden usar la API desde el navegador. | No |

## Ejemplos de uso

Entrar y obtener el token:
```
curl -X POST http://127.0.0.1:8000/login -H "Content-Type: application/json" -d "{\"usuario\": \"ana\", \"clave\": \"ana12345\"}"
```

Pedir un equipo prestado (el préstamo queda a nombre de quien dice el token):
```
curl -X POST http://127.0.0.1:8000/prestamos -H "Authorization: Bearer %TOKEN%" -H "Content-Type: application/json" -d "{\"equipo_id\": 1}"
```

## Contrato y errores

- Contrato OpenAPI: http://127.0.0.1:8000/docs
- Errores: todos responden `{"error": "...", "detalles": [...]}`. Ver [docs/errores.md](docs/errores.md).

## Cómo probarla

```
python probar_api.py
```

Resultado de la versión v1.0.0: 28 de 28 pruebas.

## Anatomía de mi API

```
Anatomía de mi API · lista de revisión (...)
0 · Cliente (2/2)
  [x] Mi proyecto tiene un cliente que consume la API: una página, una app o un script.
  ...
```

## REST y SOAP en mi proyecto

Si el laboratorio cobrara multas por entregar tarde un equipo, tendría que emitir y validar facturas, y el servicio del SAT para consultar el estado de un CFDI es SOAP. Iría como una integración externa (la pieza 7b), igual que la IA: una función que arma el sobre XML con la expresión impresa de la factura, hace un POST con tiempo máximo de espera, lee el campo `Estado` de la respuesta y lo regresa como JSON (`{"estado": "Vigente"}`). El front nunca vería XML.

## Equipo y reflexiones

(En su proyecto, aquí va cada integrante con sus palabras.)
