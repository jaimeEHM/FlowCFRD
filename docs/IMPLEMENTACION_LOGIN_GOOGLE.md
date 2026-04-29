# Implementacion de Login Google para Marcaje QR (Laravel)

Esta guia documenta la implementacion actual del login con Google usada en el flujo de marcaje QR del sistema, para replicarla en otro proyecto Laravel.

## Objetivo funcional

- Usuario escanea un QR de marcaje.
- Sistema redirige a una vista responsiva de login.
- Login permitido solo con cuenta Google institucional (`@cfrd.cl`).
- Si valida, se registra asistencia y se muestra confirmacion de marcaje.
- El codigo QR usado se regenera para mantener la logica de codigo unico por marca.

---

## Arquitectura del flujo

1. `GET /qr/scan/{codigoUnico}`
   - Valida QR.
   - Genera `token_sesion` temporal.
   - Guarda contexto en session (`qr_token`, `qr_ubicacion`, `qr_escaneado_at`).
   - Redirige a `GET /qr/marcaje`.

2. `GET /qr/marcaje`
   - Renderiza vista responsiva con boton Google Sign-In (GIS).
   - Si `GOOGLE_CLIENT_ID` no existe, avisa que Google no esta configurado.

3. Frontend (Google Identity Services)
   - Obtiene `id_token` tras autenticacion de Google.
   - Envia `POST /qr/marcaje/google` con `id_token`.

4. `POST /qr/marcaje/google`
   - Valida `id_token` contra Google `tokeninfo`.
   - Verifica dominio permitido (`GOOGLE_ALLOWED_DOMAIN`).
   - Verifica audiencia (`GOOGLE_CLIENT_ID`) si aplica.
   - Resuelve/crea usuario.
   - Registra asistencia con `record_type = entrada_qr`.
   - Marca QR como usado y regenera automaticamente.
   - Muestra vista de exito (sin redireccion automatica).

---

## Rutas involucradas

Definidas en `routes/web.php`:

- `GET /qr/scan/{codigoUnico}` -> `QrCodeController@escanear`
- `GET /qr/marcaje` -> `QrCodeController@marcaje`
- `POST /qr/marcaje/google` -> `QrCodeController@procesarMarcajeGoogle`
- `POST /qr/marcaje` -> `QrCodeController@procesarMarcaje` (flujo legacy email/password, opcional)

---

## Variables de entorno requeridas

En `.env`:

```env
GOOGLE_CLIENT_ID=tu-client-id.apps.googleusercontent.com
GOOGLE_ALLOWED_DOMAIN=cfrd.cl
```

Notas:
- `GOOGLE_ALLOWED_DOMAIN` restringe por dominio institucional.
- `GOOGLE_CLIENT_ID` permite validar `aud` del token (recomendado siempre).

Despues de cambiar `.env`:

```bash
php artisan optimize:clear
```

---

## Configuracion en Google Cloud

1. Crear/usar proyecto en Google Cloud.
2. Configurar OAuth Consent Screen.
3. Crear OAuth Client ID tipo **Web**.
4. Agregar origenes autorizados (ejemplo):
   - `https://attendance.cfrd.cl`
5. Usar ese client id en `GOOGLE_CLIENT_ID`.

---

## Implementacion backend (resumen)

Archivo principal: `app/Http/Controllers/QrCodeController.php`

Metodos relevantes:

- `marcaje()`:
  - obtiene `GOOGLE_CLIENT_ID`.
  - lo entrega a la vista para renderizar el boton GIS.

- `procesarMarcajeGoogle(Request $request)`:
  - valida `id_token`.
  - valida existencia y vigencia de session QR (`QrCode::validarTokenSesion`).
  - valida token Google (`tokeninfo`).
  - aplica restriccion de dominio.
  - crea/actualiza usuario.
  - registra asistencia.
  - finaliza y limpia session.

- `resolveUserFromGoogleIdToken(string $idToken)`:
  - encapsula validacion Google + resolucion/creacion de usuario.

Modelo QR: `app/Models/QrCode.php`

- `escanear()` -> genera token temporal.
- `validarTokenSesion($token)` -> vigencia de 5 minutos.
- `marcarComoUsado()`.
- `regenerarAutomaticamente()` -> rota codigo para mantener unicidad.

---

## Implementacion frontend (vista)

Archivo: `resources/views/qr-codes/marcaje.blade.php`

Puntos clave:

- Carga script GIS:

```html
<script src="https://accounts.google.com/gsi/client" async defer></script>
```

- Inicializa Google con `client_id` desde backend.
- Callback recibe `response.credential` (`id_token`).
- Envio por form oculto a `POST /qr/marcaje/google`.

Ejemplo simplificado:

```html
<div id="googleSignInButton"></div>
<form method="POST" action="/qr/marcaje/google" id="googleQrForm" class="d-none">
  <input type="hidden" name="id_token" id="googleIdToken">
</form>
<script>
  function handleGoogleCredentialResponse(response) {
    document.getElementById('googleIdToken').value = response.credential;
    document.getElementById('googleQrForm').submit();
  }
</script>
```

---

## Seguridad aplicada

- Validacion server-side de `id_token` contra Google (no confiar solo en frontend).
- Restriccion por dominio institucional.
- Validacion de audiencia (`aud`) contra `GOOGLE_CLIENT_ID`.
- Session QR temporal con expiracion (5 minutos).
- QR marcado como usado tras marcar + regeneracion automatica.

---

## Comportamiento posterior a marca exitosa

Vista: `resources/views/qr-codes/success.blade.php`

- Muestra confirmacion de marcaje.
- Sin redireccion automatica.
- Sin forzar navegacion a vistas privadas para evitar errores de contexto `Auth::user()`.

---

## Checklist de replicacion en otro sistema

1. Copiar rutas QR (`scan`, `marcaje`, `marcaje/google`).
2. Implementar session temporal por QR.
3. Implementar validacion de Google `id_token` en backend.
4. Agregar restriccion de dominio.
5. Agregar vista responsiva con boton GIS.
6. Registrar asistencia y auditar eventos.
7. Regenerar QR tras uso.
8. Configurar `GOOGLE_CLIENT_ID` y `GOOGLE_ALLOWED_DOMAIN`.
9. Probar con cuenta permitida y no permitida.
10. Validar escenario de token expirado y QR expirado.

---

## Pruebas recomendadas

- Caso feliz:
  - escaneo QR valido + cuenta `@cfrd.cl` -> marca correcta.
- Dominio no permitido:
  - cuenta externa -> error 403.
- Token vencido:
  - esperar >5 min antes de marcar -> error de session expirada.
- QR invalido:
  - codigo no existente/expirado/usado -> pantalla de error.

---

## Observaciones operativas

- Si Google no aparece en la vista, revisar:
  - `GOOGLE_CLIENT_ID` cargado en runtime.
  - dominio HTTPS en origanes autorizados.
  - limpieza de cache de config.
- Para alta concurrencia de monitores publicos, usar cache/reverse-proxy y mantener controlados los endpoints de stream/polling.

