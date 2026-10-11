@extends('layout')
@section('title', 'Ustawienia')
@section('content')
    <h1>Ustawienia</h1>

    <h2>Klucz do podpisywania</h2>
    @if ($signer->configured())
        <p>Klucz prywatny: <code>{{ $signer->path() }}</code>. Zrób jego kopię zapasową — bez niego nie wystawisz
            kluczy zgodnych z już działającymi instalacjami.</p>

        <h3>Do pliku <code>.env</code> każdej instalacji weCMS</h3>
        <div class="key-box" id="env-line">{{ $envLine }}</div>
        <div class="actions" style="margin-top:.75rem">
            <button type="button" class="btn secondary" data-copy="#env-line">Kopiuj</button>
            <span id="copy-status" role="status" aria-live="polite"></span>
        </div>
    @else
        <div class="alert warn" role="status">
            Brak klucza prywatnego w <code>{{ $signer->path() }}</code>. Wygeneruj go na serwerze:
            <code>php artisan licencje:keypair</code> — albo, jeśli instalacje weCMS mają już <code>LICENSE_PUBLIC_KEY</code>,
            wgraj pasujący klucz prywatny i ustaw ścieżkę w <code>LICENSE_PRIVATE_KEY_PATH</code>.
        </div>
    @endif

    <h2>Sprawdzanie licencji online</h2>
    <p>Instalacje mogą pytać o stan klucza (unieważnienie, zastąpienie):</p>
    <div class="key-box">POST {{ route('api.licenses.check') }}<br>{"key": "WCMS-…", "domain": "klient.pl"}</div>
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-copy]').forEach(function (button) {
    button.addEventListener('click', function () {
        var status = document.getElementById('copy-status');
        navigator.clipboard.writeText(document.querySelector(button.dataset.copy).textContent.trim()).then(function () {
            status.textContent = 'Skopiowano.';
        }, function () {
            status.textContent = 'Nie udało się skopiować — zaznacz tekst i skopiuj ręcznie.';
        });
    });
});
</script>
@endpush
