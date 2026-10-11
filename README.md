# Panel licencji weCMS

Zarządzanie kluczami licencyjnymi weCMS: klienci, wystawianie kluczy (wersja darmowa / płatna),
przedłużanie, unieważnianie, dziennik zmian i API do sprawdzania licencji online.

## Jak to działa

- Klucz `WCMS-…` to JSON (domena, edycja, termin, notatka) podpisany kluczem prywatnym RSA.
  Instalacja weCMS sprawdza podpis kluczem publicznym (`LICENSE_PUBLIC_KEY`) — offline, bez łączenia z panelem.
- Edycja `free` = wersja darmowa, każda inna (`standard`, `premium`, `municipality`) = płatna
  (patrz `App\Support\Edition` w feer-web). feer.org.pl zawsze ma pełną wersję, bez klucza.
- Unieważnienie i zastąpienie widać tylko w panelu i w API `POST /api/v1/licenses/check` —
  podpisany klucz sam w sobie jest ważny do daty w nim zapisanej.

## Wdrożenie (cms2.ziemowitgil.pl)

```bash
git clone https://github.com/ziemowit-gil/wecms-licencje.git
cd wecms-licencje
composer84 install --no-dev --optimize-autoloader
cp .env.example .env            # APP_URL=https://cms2.ziemowitgil.pl, APP_ENV=production, APP_DEBUG=false
php84 artisan key:generate
touch database/database.sqlite
php84 artisan migrate --force
php84 artisan licencje:keypair  # albo wgraj istniejący klucz prywatny i ustaw LICENSE_PRIVATE_KEY_PATH
php84 artisan licencje:user ziemowit.gil@feer.org.pl --name="Ziemowit Gil"
php84 artisan config:cache && php84 artisan route:cache && php84 artisan view:cache
```

Katalog domeny ma wskazywać na `public/`. Linię `LICENSE_PUBLIC_KEY=…` dla instalacji weCMS
pokazuje panel w zakładce **Ustawienia** (i polecenie `licencje:keypair`).

**Kopia zapasowa:** klucz prywatny i `database/database.sqlite`. Utrata klucza prywatnego oznacza,
że nowe klucze nie będą pasować do instalacji z dotychczasowym `LICENSE_PUBLIC_KEY`.

## Testy

```bash
php artisan test
```
