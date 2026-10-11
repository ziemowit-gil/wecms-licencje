<?php

return [
    // Klucz prywatny RSA (PEM) do podpisywania licencji. NIGDY w repozytorium ani w public/.
    // Ta sama para co LICENSE_PUBLIC_KEY w instalacjach weCMS.
    'private_key_path' => env('LICENSE_PRIVATE_KEY_PATH') ?: storage_path('app/private/license-private.pem'),

    // Edycje zapisywane w kluczu (pole „x”). weCMS traktuje każdą poza „free” jako wersję płatną
    // (App\Support\Edition w feer-web).
    'editions' => [
        'free' => 'Darmowa',
        'standard' => 'Płatna — standard',
        'premium' => 'Płatna — premium',
        'municipality' => 'Płatna — samorząd',
    ],

    // Ile dni przed wygaśnięciem licencja trafia do „wygasające”.
    'expiring_days' => 30,
];
