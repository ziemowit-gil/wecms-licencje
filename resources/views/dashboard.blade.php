@extends('layout')
@section('title', 'Pulpit')
@section('content')
    <div class="page-head">
        <h1>Pulpit</h1>
        <a class="btn" href="{{ route('licenses.create') }}">Wystaw licencję</a>
    </div>

    @unless ($signerReady)
        <div class="alert warn" role="status">
            Brak klucza prywatnego — wystawianie licencji nie zadziała. Wygeneruj go poleceniem
            <code>php artisan licencje:keypair</code> albo wskaż istniejący w <code>LICENSE_PRIVATE_KEY_PATH</code>.
        </div>
    @endunless

    <h2 class="sr-only">Podsumowanie</h2>
    <div class="grid">
        @foreach (\App\Models\License::STATUSES as $key => $label)
            <a class="stat" href="{{ route('licenses.index', ['status' => $key]) }}">
                <span class="num">{{ $counts[$key] }}</span>
                <span class="label">{{ $label }}</span>
            </a>
        @endforeach
        <a class="stat" href="{{ route('customers.index') }}">
            <span class="num">{{ $customers }}</span>
            <span class="label">Klienci</span>
        </a>
    </div>

    <h2>Wygasają w ciągu {{ config('licensing.expiring_days') }} dni</h2>
    @if ($expiring->isEmpty())
        <p class="muted">Żadna licencja nie wygasa w najbliższym czasie.</p>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th scope="col">Domena</th><th scope="col">Klient</th><th scope="col">Edycja</th><th scope="col">Ważna do</th><th scope="col"><span class="sr-only">Akcje</span></th></tr></thead>
                <tbody>
                    @foreach ($expiring as $license)
                        <tr>
                            <td><a href="{{ route('licenses.show', $license) }}">{{ $license->domain }}</a></td>
                            <td>{{ $license->customer->name }}</td>
                            <td>{{ $license->editionLabel() }}</td>
                            <td>{{ $license->expires_on->format('d.m.Y') }}</td>
                            <td><a href="{{ route('licenses.renew-form', $license) }}">Przedłuż<span class="sr-only"> licencję {{ $license->domain }}</span></a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <h2>Ostatnie zmiany</h2>
    @include('partials.activities', ['activities' => $activities])
@endsection
