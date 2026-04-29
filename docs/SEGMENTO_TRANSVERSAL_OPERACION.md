# Segmento transversal: operacion

## Objetivo

Controlar la visibilidad del segmento `Línea transversal` sin eliminarlo de la base de datos, permitiendo activarlo/desactivarlo segun necesidad operativa.

## Estado funcional actual

- `General` es el segmento operativo por defecto.
- `Línea transversal` se conserva para futura implementacion.
- Por defecto se mantiene oculto con:
  - `WORKFLOW_TRANSVERSAL_GROUP_ENABLED=false`

## Configuracion

Variables relevantes en `.env`:

- `WORKFLOW_TRANSVERSAL_GROUP_ENABLED=false`
- `WORKFLOW_TRANSVERSAL_GROUP_NAME="Línea transversal"`

## Comando de control

Se dispone del comando Artisan:

```bash
php artisan workflow:transversal-group {on|off|status} [--no-move]
```

### Modos

- `status`: muestra estado actual y nombre del segmento transversal.
- `on`: activa visualizacion del segmento transversal.
- `off`: desactiva visualizacion y mueve tareas de transversal a `General`.
- `off --no-move`: desactiva visualizacion sin mover tareas.

## Ejemplos

```bash
php artisan workflow:transversal-group status
php artisan workflow:transversal-group on
php artisan workflow:transversal-group off
php artisan workflow:transversal-group off --no-move
```

## Regla de migracion de tareas al desactivar

Cuando se ejecuta `off` (sin `--no-move`):

- busca grupos con nombre configurado (`WORKFLOW_TRANSVERSAL_GROUP_NAME`),
- asegura que exista `General` por proyecto,
- mueve cada tarea de transversal a `General`,
- recalcula `kanban_order` al final de su columna (`status`).

## Impacto en UI

Con `WORKFLOW_TRANSVERSAL_GROUP_ENABLED=false`:

- Kanban y lista ocultan `Línea transversal`.
- La operacion diaria queda concentrada en `General`.

Con `WORKFLOW_TRANSVERSAL_GROUP_ENABLED=true`:

- Kanban y lista muestran nuevamente `Línea transversal`.

## Notas para implementacion futura

- Mantener `Línea transversal` en BD permite habilitar flujos cross-proyecto sin migraciones destructivas.
- Si se reactiva de forma definitiva, evaluar:
  - reglas de creacion de tareas por segmento,
  - reportes separados (`General` vs transversal),
  - pruebas de regresion en Kanban/lista.
