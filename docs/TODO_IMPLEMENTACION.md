# To-do list de implementacion

## Prioridad P0 (inmediata)

- [ ] Crear matriz de rutas con mutacion y marcar cobertura actual (web + API).
- [ ] Implementar tests Feature para `pmo.proyectos.store` y `pmo.proyectos.update`.
- [ ] Implementar tests de coordinacion: backlog y validacion de urgentes.
- [ ] Implementar tests de workspace proyecto: creacion/edicion en kanban y minutas.
- [ ] Agregar script unico de smoke check local (`migrate`, `seed`, `build`, `test`).

## Prioridad P1 (siguiente bloque)

- [ ] Diseñar contrato API para backlog/validaciones (request/response/errores).
- [ ] Implementar endpoints API faltantes con politicas de autorizacion.
- [ ] Cubrir endpoints nuevos con tests API.
- [ ] Incorporar paginacion/filtros server-side en vistas con mayor volumen.
- [ ] Medir tiempos de respuesta de tablero macro en cartera completa.

## Prioridad P2 (integraciones y producto)

- [ ] Implementar servicio LRS MVP para eventos base.
- [ ] Añadir trazabilidad en UI de estado LRS (ultimo intento, resultado).
- [ ] Definir politica de reintentos y backoff para envio LRS.
- [ ] Publicar guia de integracion externa para cliente desktop/API.
- [ ] Definir backlog de parity total web/API por modulo.

---

## To-do por modulo

## PMO

- [ ] Validar cobertura de creacion/edicion de proyecto con archivo acta.
- [ ] Probar reglas de visibilidad por perfil y segmento.
- [ ] Agregar pruebas de filtros/ordenes de cartera.

## Coordinacion

- [ ] Probar flujo completo backlog -> validacion -> asignacion.
- [ ] Verificar reglas de urgentes y casos borde.
- [ ] Cubrir validacion de skills y notificaciones asociadas.

## Proyecto

- [ ] Probar tabla y cronograma con datos reales y volumen.
- [ ] Cubrir creacion/edicion de tareas por API y por web.
- [ ] Probar minutas: creacion, permisos y trazabilidad.

## Talento y Sistema

- [ ] Cubrir consultas y filtros de matriz/relaciones.
- [ ] Probar auditoria y notificaciones con escenarios reales.
- [ ] Cerrar gap funcional de LRS (de estado a operacion).

---

## Definition of Done propuesta (por tarea)

- [ ] Requisito funcional implementado.
- [ ] Cobertura de pruebas agregada o actualizada.
- [ ] Documentacion tecnica/funcional actualizada en `docs/`.
- [ ] Build y tests en verde.
- [ ] Validacion manual basica de UX completada.
