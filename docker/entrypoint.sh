#!/bin/sh
set -e

echo "--------------------------------------------------"
echo "Démarrage du conteneur AniCare"
echo "--------------------------------------------------"

APP_DIR="/var/www/html"

cd "$APP_DIR"

echo "Création des dossiers nécessaires..."

mkdir -p var/cache
mkdir -p var/log
mkdir -p public/uploads
mkdir -p public/uploads/animals
mkdir -p public/uploads/health-book-entries

echo "Application des permissions..."

chown -R www-data:www-data var public/uploads || true
chmod -R 775 var public/uploads || true

echo "Vérification des dépendances Composer..."

if [ ! -d "vendor" ]; then
    echo "Installation des dépendances Composer..."
    composer install --no-interaction --prefer-dist
else
    echo "Les dépendances Composer sont déjà installées."
fi

echo "Vérification de la base de données..."

DATABASE_HOST="${DATABASE_HOST:-database}"
DATABASE_PORT="${DATABASE_PORT:-3306}"

echo "Attente de MySQL sur ${DATABASE_HOST}:${DATABASE_PORT}..."

MAX_ATTEMPTS=30
ATTEMPT=1

until php -r "
    \$host = getenv('DATABASE_HOST') ?: 'database';
    \$port = (int) (getenv('DATABASE_PORT') ?: 3306);
    \$connection = @fsockopen(\$host, \$port, \$errno, \$errstr, 2);

    if (\$connection) {
        fclose(\$connection);
        exit(0);
    }

    exit(1);
"; do
    if [ "$ATTEMPT" -ge "$MAX_ATTEMPTS" ]; then
        echo "Impossible de se connecter à MySQL après ${MAX_ATTEMPTS} tentatives."
        echo "Le conteneur continue quand même son démarrage."
        break
    fi

    echo "MySQL indisponible, tentative ${ATTEMPT}/${MAX_ATTEMPTS}..."
    ATTEMPT=$((ATTEMPT + 1))
    sleep 2
done

echo "Base de données disponible."

if [ -f "bin/console" ]; then
    echo "Nettoyage du cache Symfony..."
    php bin/console cache:clear --no-warmup || true
fi

if [ "$RUN_MIGRATIONS" = "1" ]; then
    echo "Exécution automatique des migrations Doctrine..."
    php bin/console doctrine:migrations:migrate --no-interaction
else
    echo "Migrations non lancées automatiquement."
    echo "Commande manuelle :"
    echo "docker compose exec app php bin/console doctrine:migrations:migrate"
fi

echo "--------------------------------------------------"
echo "AniCare est prêt."
echo "Lancement Apache..."
echo "--------------------------------------------------"

exec "$@"