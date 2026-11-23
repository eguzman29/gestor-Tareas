# 1. Usar JWT para la autenticación

- **Fecha:** 2026-11-10
- **Estado:** Aceptada

## Contexto

La API necesita saber quién hace cada petición para aplicar roles y dueños. HTTP no recuerda nada entre peticiones. Las opciones eran guardar sesiones en el servidor o entregar un token firmado que el cliente manda cada vez.

## Decisión

Usamos un token JWT firmado con HS256, con el usuario y el rol, que vence a los 30 minutos. El secreto se lee de la variable de entorno `JWT_SECRETO`.

## Consecuencias

- La API no guarda sesiones: funciona igual aunque corran varias copias.
- El contenido del token se puede leer, así que no lleva nada secreto.
- Un token robado sirve hasta que vence; por eso dura solo 30 minutos.
