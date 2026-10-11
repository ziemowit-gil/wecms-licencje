@extends('layout')
@section('title', 'Licencja '.$license->domain)
@section('content')
    <div class="page-head">
        <h1>Licencja {{ $license->domain }}</h1>
        <div class="actions">
            <a class="btn secondary" href="{{ route('licenses.download', $license) }}">Pobierz plik .txt</a>
            @if ($license->isCurrent())
                <a class="btn" href="{{ route('licenses.renew-form', $license) }}">Przedłuż / zmień</a>
            @endif
        </div>
    </div>

    @if ($license->replacedBy)
        <div class="alert warn" role="status">Ten klucz zastąpiono nowym: <a href="{{ route('licenses.show', $license->replacedBy) }}">licencja #{{ $license->replacedBy->id }}</a>.</div>
    @endif
    @if ($license->revoked_at)
        <div class="alert error" role="status">Unieważniona {{ $license->revoked_at->format('d.m.Y H:i') }}: {{ $license->revoke_reason }}</div>
    @endif

    <div class="card">
        <dl class="details">
            <dt>Stan</dt><dd>@include('partials.status')</dd>
            <dt>Klient</dt><dd><a href="{{ route('customers.show', $license->customer) }}">{{ $license->customer->name }}</a></dd>
            <dt>Domena</dt><dd>{{ $license->domain === '*' ? 'dowolna (*)' : $license->domain }}</dd>
            <dt>Edycja</dt><dd>{{ $license->editionLabel() }}</dd>
            <dt>Ważna do</dt><dd>{{ $license->expires_on?->format('d.m.Y') ?? 'bezterminowo' }}</dd>
            <dt>Wystawiona</dt><dd>{{ $license->issued_on->format('d.m.Y') }}{{ $license->issuer ? ' przez '.$license->issuer->name : '' }}</dd>
            @if ($license->note)<dt>Notatka</dt><dd>{{ $license->note }}</dd>@endif
            @if ($replaces)<dt>Zastępuje</dt><dd><a href="{{ route('licenses.show', $replaces) }}">licencję #{{ $replaces->id }}</a></dd>@endif
            <dt>Ostatnie sprawdzenie</dt>
            <dd>{{ $license->last_check_at ? $license->last_check_at->format('d.m.Y H:i').' z '.$license->last_check_domain : 'instalacja jeszcze nie sprawdzała licencji online' }}</dd>
        </dl>
    </div>

    <h2 id="key-heading">Klucz</h2>
    <p class="muted">Klient wkleja go do pliku <code>.env</code> instalacji jako <code>LICENSE_KEY=…</code> (albo podaje w instalatorze).</p>
    <div class="key-box" id="license-key">{{ $license->license_key }}</div>
    <div class="actions" style="margin-top:.75rem">
        <button type="button" class="btn secondary" data-copy="#license-key">Kopiuj klucz</button>
        <span id="copy-status" role="status" aria-live="polite"></span>
    </div>

    @if ($license->isCurrent())
        <h2>Unieważnij</h2>
        <p>Klucz zostaje oznaczony jako unieważniony w panelu i w API sprawdzania licencji.
            Instalacja, która sprawdza licencję tylko offline (sam podpis), będzie go uznawać do daty ważności.</p>
        <form method="post" action="{{ route('licenses.revoke', $license) }}" class="card"
              data-confirm="Unieważnić licencję {{ $license->domain }}? Tej operacji nie można cofnąć.">
            @csrf
            @include('partials.field', ['name' => 'reason', 'label' => 'Powód', 'required' => true])
            <button type="submit" class="btn danger">Unieważnij licencję</button>
        </form>
    @endif

    <h2>Historia</h2>
    @include('partials.activities', ['activities' => $activities])
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-copy]').forEach(function (button) {
    button.addEventListener('click', function () {
        var text = document.querySelector(button.dataset.copy).textContent.trim();
        var status = document.getElementById('copy-status');
        navigator.clipboard.writeText(text).then(function () {
            status.textContent = 'Skopiowano klucz do schowka.';
        }, function () {
            status.textContent = 'Nie udało się skopiować — zaznacz klucz i skopiuj ręcznie.';
        });
    });
});
</script>
@endpush
