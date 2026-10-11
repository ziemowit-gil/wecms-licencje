#!/usr/bin/env bash
# Wdrożenie panelu licencji na MyDevil (FreeBSD, DevilWEB).
#
#   cd ~/wecms-licencje && git pull && bash deploy/mydevil.sh cmslicense.feer.org.pl
#
# Wcześniej (raz): `devil www add cmslicense.feer.org.pl php` i rekord A domeny
# wskazujący na IP serwera (devil vhost list public). Skrypt można uruchamiać
# ponownie — przy aktualizacji robi tylko composer, migracje i cache.
set -euo pipefail

DOMAIN="${1:-cmslicense.feer.org.pl}"
APP="$(cd "$(dirname "$0")/.." && pwd)"
DOMAIN_DIR="$HOME/domains/$DOMAIN"
PHP=/usr/local/bin/php84
COMPOSER=/usr/local/bin/composer2

[ -d "$DOMAIN_DIR" ] || { echo "Brak $DOMAIN_DIR — najpierw: devil www add $DOMAIN php"; exit 1; }

echo "→ PHP 8.4 dla domeny"
grep -q 'x-httpd-php84' "$DOMAIN_DIR/.htaccess" 2>/dev/null \
    || echo 'AddType application/x-httpd-php84 .php' >> "$DOMAIN_DIR/.htaccess"

echo "→ public_html → $APP/public"
if [ ! -L "$DOMAIN_DIR/public_html" ]; then
    [ -d "$DOMAIN_DIR/public_html" ] && mv "$DOMAIN_DIR/public_html" "$DOMAIN_DIR/public_html.domyslny"
    ln -s "$APP/public" "$DOMAIN_DIR/public_html"
fi
devil www options "$DOMAIN" php_openbasedir "$APP/:$DOMAIN_DIR/:/tmp/" >/dev/null || true

cd "$APP"

echo "→ composer install"
$PHP $COMPOSER install --no-dev --optimize-autoloader --no-interaction

if [ ! -f .env ]; then
    echo "→ .env"
    cp .env.example .env
    sed -i '' -e 's|^APP_ENV=.*|APP_ENV=production|' \
              -e 's|^APP_DEBUG=.*|APP_DEBUG=false|' \
              -e "s|^APP_URL=.*|APP_URL=https://$DOMAIN|" .env
    $PHP artisan key:generate --force
fi
chmod 600 .env

touch database/database.sqlite
$PHP artisan migrate --force

if [ ! -f storage/app/private/license-private.pem ]; then
    echo "→ klucz do podpisywania licencji"
    $PHP artisan licencje:keypair
fi

$PHP artisan config:cache
$PHP artisan route:cache
$PHP artisan view:cache
devil www restart "$DOMAIN" >/dev/null || true

echo
echo "Gotowe: https://$DOMAIN"
echo "Konto w panelu:  $PHP artisan licencje:user twoj@adres.pl --name=\"Imię Nazwisko\""
echo "SSL:             devil vhost list public   # odczytaj IP, potem:"
echo "                 devil ssl www add <IP> le le $DOMAIN"
