# Roadmap de implementacion

## Objetivo

Consolidar Workflow CFRD como plataforma operativa estable, con cobertura de pruebas en flujos criticos, paridad API para integraciones y mejora progresiva de rendimiento.

---

## Fase 0 - Estabilizacion base (1 semana)

## Objetivos

- Congelar base tecnica actual y evitar regresiones.
- Alinear entorno local/deploy/documentacion para el equipo.

## Entregables

- Checklist de setup validado en `README`.
- Scripts de verificacion (`build`, `test`, `migrate`, `seed`) estandarizados.
- Auditoria de rutas mutables y responsables funcionales.

## Criterio de cierre

- Cualquier dev levanta proyecto + seed + login + modulos principales sin friccion.

---

## Fase 1 - Cobertura de pruebas en flujos criticos (2 semanas)

## Objetivos

- Cubrir mutaciones de alto impacto en PMO, coordinacion y proyecto.

## Entregables

- Feature tests para:
  - `pmo.proyectos.store/update`,
  - backlog y validaciones de coordinacion,
  - kanban sync y creacion de grupos/tareas,
  - minutas.
- Matriz ruta->test en documentacion.

## Criterio de cierre

- Flujos criticos con test passing en CI y base de regresion minima.

---

## Fase 2 - Paridad API para integraciones (2-3 semanas)

## Objetivos

- Exponer via API las operaciones de negocio hoy exclusivas de web.

## Entregables

- Endpoints versionados para backlog/validaciones/kanban/minutas.
- Politicas de autorizacion coherentes con RBAC actual.
- Tests API por endpoint nuevo.

## Criterio de cierre

- Un cliente externo puede ejecutar flujo operativo principal sin depender de UI web.

---

## Fase 3 - LRS MVP funcional (1-2 semanas)

## Objetivos

- Pasar de configuracion/estado a integracion funcional minima.

## Entregables

- Servicio de envio de eventos clave (tarea creada, tarea completada, validacion).
- Reintentos basicos y logging de errores.
- Vista de estado con ultimo envio, exito/falla y diagnostico.

## Criterio de cierre

- Eventos seleccionados llegan al LRS y quedan trazables desde sistema.

---

## Fase 4 - Rendimiento y UX avanzada (2 semanas)

## Objetivos

- Mejorar experiencia en cartera grande y paneles de trabajo intensivo.

## Entregables

- Paginacion y filtros server-side en vistas pesadas.
- Carga incremental para tablero macro/lista/kanban cuando aplique.
- Ajustes visuales y de usabilidad de alto uso (cronograma, carga, lista).

## Criterio de cierre

- Tiempos de respuesta y fluidez aceptables en escenarios con datos grandes.

---

## Fase 5 - Cierre de producto y operacion (1 semana)

## Objetivos

- Dejar la plataforma lista para iteracion continua.

## Entregables

- Definition of Done del equipo.
- Guia de release y checklist de despliegue.
- Backlog priorizado de siguientes mejoras.

## Criterio de cierre

- Cadencia de entrega predecible con calidad y observabilidad.
