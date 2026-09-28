# API de E-commerce Segura con Swagger Completo

API REST construida con **Laravel 12**, **JWT** (tymon/jwt-auth), **Stripe** (pagos) y **Swagger/OpenAPI** (l5-swagger), corriendo sobre **MySQL 8** en Docker.

## Contenido del proyecto

```
app/Http/Controllers/Api/   -> AuthController, ProductController, OrderController, PaymentController
app/Http/Requests/          -> Form Requests (validación)
app/Models/                 -> User, Product, Order, OrderItem, Payment
app/Services/StripeService.php
database/migrations/        -> users, products, orders, order_items, payments
database/seeders/           -> Admin, cliente demo y 10+ productos de ejemplo
routes/api.php               -> Todas las rutas de la API
docker-compose.yml, Dockerfile, docker/                    -> Infraestructura
```

## Lo único que TÚ debes hacer

1. **Copiar el archivo de entorno**:
   ```bash
   cp .env.example .env
   ```

2. **Conseguir tus llaves de Stripe** (modo test) en https://dashboard.stripe.com/test/apikeys
   y pegarlas en `.env`:
   ```
   STRIPE_KEY=pk_test_...
   STRIPE_SECRET=sk_test_...
   ```

3. **(Opcional pero recomendado) Webhook de Stripe**: si quieres que Stripe notifique el
   resultado del pago automáticamente al endpoint `POST /api/payments/webhook`, crea un
   webhook en https://dashboard.stripe.com/test/webhooks apuntando a:
   `http://TU_DOMINIO_PUBLICO:8080/api/payments/webhook` (en local puedes usar `stripe listen`
   o `ngrok` para exponer el puerto 8080), evento mínimo: `payment_intent.succeeded` y
   `payment_intent.payment_failed`. Copia el "Signing secret" a:
   ```
   STRIPE_WEBHOOK_SECRET=whsec_...
   ```

   > **Nota sobre JWT**: NO necesitas conseguir nada para JWT. El contenedor genera
   > automáticamente `JWT_SECRET` la primera vez que arranca (`php artisan jwt:secret`).
   > Tampoco necesitas generar `APP_KEY` a mano, también se genera solo.

4. **Levantar todo con Docker**:
   ```bash
   docker compose up -d --build
   ```
   Esto construye la imagen PHP, instala dependencias con Composer, espera a que MySQL
   esté listo, genera `APP_KEY` y `JWT_SECRET`, corre las migraciones, siembra la base de
   datos (seeders) y genera la documentación Swagger — todo automáticamente.

5. **Listo.** La API queda disponible en:
   - API: `http://localhost:8080/api/...`
   - Swagger UI: `http://localhost:8080/api/documentation`
   - phpMyAdmin: `http://localhost:8081` (usuario `root`, password el de `DB_ROOT_PASSWORD`)

## Usuarios de prueba (creados por el seeder)

| Rol      | Email                | Password     |
|----------|-----------------------|--------------|
| Admin    | admin@example.com     | Admin123!    |
| Cliente  | cliente@example.com   | Cliente123!  |

## Flujo típico de prueba

```bash
# 1) Login
curl -X POST http://localhost:8080/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"cliente@example.com","password":"Cliente123!"}'
# -> copiar el access_token de la respuesta

# 2) Ver catálogo (público, no requiere token)
curl http://localhost:8080/api/products

# 3) Crear una orden
curl -X POST http://localhost:8080/api/orders \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"items":[{"product_id":1,"quantity":2}]}'

# 4) Crear el PaymentIntent en Stripe para esa orden
curl -X POST http://localhost:8080/api/payments/create-intent \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"order_id":1}'
# -> usar el client_secret devuelto en el frontend (Stripe.js / Stripe Elements)
#    para completar el pago con una tarjeta de prueba, ej. 4242 4242 4242 4242

# 5) Confirmar/consultar el estado del pago
curl -X POST http://localhost:8080/api/payments/confirm \
  -H "Authorization: Bearer TU_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"payment_intent_id":"pi_..."}'

# 6) Ver historial de compras
curl http://localhost:8080/api/orders -H "Authorization: Bearer TU_TOKEN"
```

## Endpoints principales

| Método | Ruta                          | Auth        | Descripción                              |
|--------|--------------------------------|-------------|-------------------------------------------|
| POST   | /api/auth/register             | No          | Registro de cliente                       |
| POST   | /api/auth/login                | No          | Login, devuelve JWT                       |
| POST   | /api/auth/logout               | Sí          | Invalida el token                         |
| POST   | /api/auth/refresh              | Sí          | Refresca el token                         |
| GET    | /api/auth/me                   | Sí          | Datos del usuario autenticado             |
| GET    | /api/products                  | No          | Listado público de productos              |
| GET    | /api/products/{id}              | No          | Detalle de un producto                    |
| POST   | /api/products                  | Sí (admin)  | Crear producto                            |
| PUT    | /api/products/{id}              | Sí (admin)  | Editar producto                           |
| DELETE | /api/products/{id}              | Sí (admin)  | Eliminar producto                         |
| POST   | /api/orders                    | Sí          | Crear orden de compra                     |
| GET    | /api/orders                    | Sí          | Historial de compras del usuario          |
| GET    | /api/orders/{id}                 | Sí          | Detalle de una orden                       |
| POST   | /api/payments/create-intent    | Sí          | Crear PaymentIntent de Stripe             |
| POST   | /api/payments/confirm          | Sí          | Confirmar estado del pago                 |
| POST   | /api/payments/webhook          | No*         | Webhook de Stripe (*validado por firma)   |

Toda la documentación interactiva (probar cada endpoint desde el navegador) está en
`/api/documentation`.

## Si ves "502/503 Bad Gateway"

Casi siempre significa que el contenedor `app` se reinició/murió porque no encontró
`vendor/autoload.php`. Verifícalo con:

```bash
docker compose ps          # ¿el contenedor "app" dice "Restarting"?
docker compose logs app    # busca "vendor/autoload.php" o "Fatal error"
```

Si es así, asegúrate de tener la versión más reciente de este `docker-compose.yml`
(el servicio `app` **no** debe montar todo el repo como volumen, solo `.env` — de lo
contrario tapa el `vendor/` que Composer instaló dentro de la imagen) y reconstruye:

```bash
docker compose down
docker compose up -d --build
```

## Comandos útiles

```bash
# Ver logs
docker compose logs -f app

# Entrar al contenedor
docker compose exec app sh

# Volver a correr migraciones/seeders manualmente
docker compose exec app php artisan migrate:fresh --seed

# Regenerar documentación Swagger tras cambiar anotaciones
docker compose exec app php artisan l5-swagger:generate

# Apagar todo
docker compose down

# Apagar y borrar datos de MySQL
docker compose down -v
```

## Arquitectura de seguridad implementada

- Autenticación **JWT** (guard `api`), tokens con expiración (`JWT_TTL`) y refresh.
- Autorización por rol: middleware `is.admin` protege creación/edición/borrado de productos.
- Cada orden y pago valida que pertenezca al usuario autenticado (o sea admin).
- Validación de entrada centralizada en **Form Requests**, con mensajes de error en español.
- Manejo de errores homogéneo en JSON (`bootstrap/app.php` -> `withExceptions`), sin exponer
  trazas de error en producción (`APP_DEBUG=false`).
- Verificación de firma de los webhooks de Stripe (`Stripe\Webhook::constructEvent`) antes de
  procesar cualquier evento.
- Transacciones de base de datos con bloqueo de fila (`lockForUpdate`) al crear órdenes, para
  evitar condiciones de carrera sobre el stock.
- Passwords hasheados con bcrypt, nunca expuestos en las respuestas (`$hidden` en el modelo `User`).

## Notas de versiones

Se usan versiones compatibles y estables al momento de escribir esto: Laravel ^12,
tymon/jwt-auth ^2.1 (compatible con Laravel 11/12), stripe/stripe-php ^15, darkaonline/l5-swagger ^8.6,
PHP 8.3, MySQL 8.0. Si al construir la imagen Composer resuelve versiones ligeramente distintas
por actualizaciones posteriores del ecosistema, no debería romper nada porque todas usan
Semantic Versioning con `^`.
