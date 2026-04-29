# Analisis del estado actual

## 1) Flujo de vistas y modulos implementados

## Web Inertia

- **Acceso y base**
  - `Welcome`, login/password, OAuth Google, dashboard.
  - Ajustes: perfil y seguridad.
- **PMO**
  - `pmo/tablero-macro` con segmentos: cartera, KPI, Gantt, lista, kanban, carga.
  - Gestion PMO de proyectos (crear/editar/estado/acta constitucion).
- **Coordinacion**
  - Equipos y carga, backlog de tareas, validacion de avances urgentes.
- **Proyecto**
  - Tabla, cronograma, calendario, kanban, minutas.
- **Colaborador**
  - Mis tareas y urgentes.
- **Talento**
  - Matriz de skills y mapa de relaciones.
- **Sistema**
  - Auditoria, notificaciones y vista LRS (estado/configuracion).

## API (`/api/v1`)

- Auth token + usuario autenticado.
- Mis tareas.
- Proyectos (index/store/show/update).
- Tareas por proyecto y update de tarea.
- Autorizacion de broadcasting (`/broadcasting/auth`) con Sanctum.

## Infra y plataforma

- Stack docker integrado a Traefik + Postgres + Redis.
- Reverb operativo en contenedor propio.
- Build frontend estabilizado para bindings nativos (rolldown/rollup).

---

## 2) Lo que falta o esta incompleto

## Integraciones

- **LRS/xAPI**: existe pantalla/configuracion, pero no hay flujo de envio funcional consolidado.
- **Desktop/cliente externo**: existe contrato API base, pero no hay parity completa con operaciones web.

## Paridad funcional Web vs API

- Acciones clave de negocio estan solo en web:
  - crear/gestionar backlog y validaciones,
  - operaciones avanzadas de kanban/grupos,
  - minutas y otras operaciones de workspace.
- Faltan endpoints equivalentes si se requiere multicliente completo.

## Calidad y pruebas

- Cobertura fuerte en auth, RBAC y API base.
- Cobertura baja en flujos de escritura de coordinacion/proyecto/sistema.
- Falta cobertura sistematica de regresion para operaciones PMO de extremo a extremo.

## Rendimiento y escalabilidad

- Varias vistas trabajan con colecciones grandes sin paginacion o limites.
- Riesgo de degradacion al crecer cartera/tareas/usuarios.

---

## 3) Riesgos actuales

- Regresiones silenciosas en flujos de negocio por baja cobertura en mutaciones.
- Desalineacion entre experiencia web y capacidades de integracion externa.
- Cuellos de rendimiento en vistas de cartera y tableros al aumentar volumen.

---

## 4) Oportunidades inmediatas

- Subir cobertura de pruebas en rutas de mutacion prioritarias.
- Implementar paginacion/filtros server-side donde hay mayor volumen.
- Definir roadmap de parity API para operaciones actualmente exclusivas web.
- Cerrar vertical de LRS con una primera version funcional (MVP).

---

## 5) Nota funcional sobre segmentos de tareas

- El segmento `General` es el contenedor operativo por defecto.
- El segmento `Línea transversal` se mantiene en BD para futura implementacion, pero puede mantenerse oculto con:
  - `WORKFLOW_TRANSVERSAL_GROUP_ENABLED=false`
- Estado actual recomendado:
  - tareas activas en `General`,
  - `Línea transversal` reservada para una futura estrategia cross-proyecto.
