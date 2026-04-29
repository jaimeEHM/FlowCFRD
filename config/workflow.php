<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dominio CFRD mostrado en login / textos (sufijo sin @)
    |--------------------------------------------------------------------------
    */

    'cfrd_email_domain' => env('WORKFLOW_CFRD_DOMAIN', 'cfrd.cl'),

    /*
    |--------------------------------------------------------------------------
    | Dominios de correo aceptados (OAuth Google y resolución de usuario)
    |--------------------------------------------------------------------------
    |
    | Lista separada por comas. Debe incluir el dominio de `cfrd_email_domain`
    | y, si aplica, @udec.cl para cuentas sembradas con correo UdeC.
    |
    */

    'cfrd_email_domains' => (function (): array {
        $domains = array_values(array_unique(array_filter(array_map(
            'trim',
            explode(',', (string) env('WORKFLOW_CFRD_DOMAINS', 'cfrd.cl,udec.cl'))
        ))));

        return $domains !== [] ? $domains : ['cfrd.cl', 'udec.cl'];
    })(),

    /*
    |--------------------------------------------------------------------------
    | Acceso por contraseña (solo construcción / desarrollo)
    |--------------------------------------------------------------------------
    |
    | Cuentas permitidas para usar el formulario email/contraseña de Fortify.
    | Se puede definir una lista separada por coma en WORKFLOW_DEV_PASSWORD_EMAILS.
    | Si no existe, cae al valor legacy de WORKFLOW_DEV_PASSWORD_EMAIL.
    |
    */

    'dev_password_login_email' => env('WORKFLOW_DEV_PASSWORD_EMAIL', 'admin@cfrd.cl'),
    'dev_password_login_emails' => (function (): array {
        $csv = (string) env('WORKFLOW_DEV_PASSWORD_EMAILS', '');
        $emails = $csv !== ''
            ? explode(',', $csv)
            : [env('WORKFLOW_DEV_PASSWORD_EMAIL', 'admin@cfrd.cl')];

        $normalized = array_values(array_unique(array_filter(array_map(
            fn ($email) => strtolower(trim((string) $email)),
            $emails
        ))));

        return $normalized !== [] ? $normalized : ['admin@cfrd.cl'];
    })(),

    /*
    |--------------------------------------------------------------------------
    | Segmento transversal (Kanban / Lista)
    |--------------------------------------------------------------------------
    |
    | Se mantiene en BD para futura implementacion, pero puede ocultarse de la
    | UI operativa. Si esta deshabilitado, las tareas deben operar en "General".
    |
    */

    'transversal_group' => [
        'enabled' => (bool) env('WORKFLOW_TRANSVERSAL_GROUP_ENABLED', false),
        'name' => env('WORKFLOW_TRANSVERSAL_GROUP_NAME', 'Línea transversal'),
    ],

    /*
    |--------------------------------------------------------------------------
    | LRS / xAPI (integración futura)
    |--------------------------------------------------------------------------
    */

    'lrs' => [
        'enabled' => (bool) env('WORKFLOW_LRS_ENABLED', false),
        'endpoint' => env('WORKFLOW_LRS_ENDPOINT'),
        'key' => env('WORKFLOW_LRS_KEY'),
    ],

];
