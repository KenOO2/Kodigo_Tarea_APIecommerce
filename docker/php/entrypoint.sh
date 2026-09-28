#!/usr/bin/env bash
set -e

cd /var/www/html

echo ">> Verificando archivo .env..."
if [ ! -f .env ]; then
    echo ">> No se encontró .env, copiando desde .env.example"
    cp .env.example .env
fi

# --- Sanear variables de entorno (Windows CRLF fix) ---
# Docker Compose, al cargar env_file en Windows con un .env guardado con
# saltos de línea CRLF, puede dejar un carácter \r pegado al final de cada
# valor. Eso hace que, por ejemplo, DB_PASSWORD="ecommerce_password\r" no
# coincida con la contraseña real en MySQL, y la conexión falla en silencio.
# Aquí limpiamos \r de TODAS las variables de entorno antes de usarlas.
echo ">> Saneando variables de entorno (removiendo posibles \\r de Windows)..."
while IFS='=' read -r name value; do
    case "$name" in
        ''|*[!a-zA-Z0-9_]*) continue ;;
    esac
    cleaned="${value%$'\r'}"
    if [ "$cleaned" != "$value" ]; then
        export "$name=$cleaned"
    fi
done < <(env)

# También limpiamos el archivo .env físico (para que Laravel/artisan, que lo
# vuelve a leer por su cuenta, tampoco arrastre el \r).
sed -i 's/\r$//' .env 2>/dev/null || true

DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"

echo ">> Esperando a que MySQL (${DB_HOST}:${DB_PORT}) esté disponible..."
ATTEMPTS=0
MAX_ATTEMPTS=60
LAST_ERROR=""

until [ "$ATTEMPTS" -ge "$MAX_ATTEMPTS" ]; do
    LAST_ERROR=$(php -r "
        try {
            new PDO('mysql:host=${DB_HOST};port=${DB_PORT}', '${DB_USERNAME}', '${DB_PASSWORD}');
            exit(0);
        } catch (\Throwable \$e) {
            fwrite(STDERR, \$e->getMessage());
            exit(1);
        }
    " 2>&1 1>/dev/null) && break

    ATTEMPTS=$((ATTEMPTS + 1))
    echo "   MySQL no disponible todavía (intento ${ATTEMPTS}/${MAX_ATTEMPTS}): ${LAST_ERROR}"
    sleep 2
done

if [ "$ATTEMPTS" -ge "$MAX_ATTEMPTS" ]; then
    echo ">> ERROR: no fue posible conectar a MySQL tras ${MAX_ATTEMPTS} intentos."
    echo ">> Último error: ${LAST_ERROR}"
    echo ">> Revisa que DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME y DB_PASSWORD en tu .env"
    echo ">> coincidan exactamente con los del servicio 'mysql' en docker-compose.yml,"
    echo ">> y que el .env no tenga espacios, comillas o saltos de línea CRLF."
    exit 1
fi

echo ">> MySQL disponible."

# Generar APP_KEY si no existe
if ! grep -q "^APP_KEY=base64" .env; then
    echo ">> Generando APP_KEY..."
    php artisan key:generate --force
fi

# Generar JWT_SECRET si no existe
if [ -z "$(grep '^JWT_SECRET=' .env | cut -d '=' -f2)" ]; then
    echo ">> Generando JWT_SECRET..."
    php artisan jwt:secret --force
fi

echo ">> Cacheando configuración..."
php artisan config:clear

echo ">> Ejecutando migraciones..."
php artisan migrate --force

if [ "${DB_SEED:-true}" = "true" ]; then
    echo ">> Ejecutando seeders..."
    php artisan db:seed --force || true
fi

echo ">> Generando documentación Swagger..."
php artisan l5-swagger:generate || true

echo ">> Creando enlace simbólico de storage..."
php artisan storage:link || true

echo ">> Listo. Iniciando: $@"
exec "$@"
