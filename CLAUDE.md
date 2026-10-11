# Panel licencji weCMS

Laravel 13, SQLite, bez kroku budowania front-endu (CSS w `public/css/panel.css`).
Docelowo: https://cmslicense.feer.org.pl (hosting z `php84`).

- Wystawia klucze licencyjne weCMS. Format klucza (`app/Licensing/LicenseKey.php`) MUSI zgadzać się
  z `App\Support\LicenseValidator` w repo feer-web — test `test_key_format_matches_wecms_validator`
  sprawdza to na żywym pliku z `../feer-web`.
- Klucz to podpisany payload (RSA SHA-256): raz wystawionego nie zmieniamy. Przedłużenie/zmiana edycji
  = nowy wiersz `licenses`, stary dostaje `replaced_by_id`.
- Klucz prywatny: `LICENSE_PRIVATE_KEY_PATH` (domyślnie `storage/app/private/license-private.pem`).
  Nigdy w repo. Nowy klucz prywatny unieważnia wszystkie wydane licencje.
- Brak rejestracji — konta: `php artisan licencje:user email --name=…`.
- UI po polsku, WCAG (etykiety, aria-describedby dla błędów, kontrast ≥ 4,5:1), płasko, kolory FEER.
- Commity: Conventional Commits po polsku, bez linii Co-Authored-By.
