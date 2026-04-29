# Documentacion de implementacion Workflow CFRD

Este directorio centraliza el estado funcional del proyecto y el plan de implementacion incremental.

## Contenido

- `ANALISIS_ESTADO_ACTUAL.md`: inventario de vistas, modulos, API y cobertura actual.
- `ROADMAP_IMPLEMENTACION.md`: roadmap por fases con objetivos, entregables y criterios de cierre.
- `TODO_IMPLEMENTACION.md`: lista accionable de tareas priorizadas para comenzar ejecucion.
- `SEGMENTO_TRANSVERSAL_OPERACION.md`: guia de activacion/desactivacion del segmento `Línea transversal`.

## Comandos utiles

- `php artisan workflow:transversal-group status`
- `php artisan workflow:transversal-group on`
- `php artisan workflow:transversal-group off`
- `php artisan workflow:transversal-group off --no-move`

## Orden recomendado de lectura

1. Analisis de estado actual.
2. Roadmap por fases.
3. To-do operativo.
