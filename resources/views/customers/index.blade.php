@extends('layout')
@section('title', 'Klienci')
@section('content')
    <div class="page-head">
        <h1>Klienci</h1>
        <a class="btn" href="{{ route('customers.create') }}">Dodaj klienta</a>
    </div>

    <form method="get" class="filters" role="search" aria-label="Szukaj klientów">
        <div class="field">
            <label for="q">Szukaj</label>
            <input id="q" type="search" name="q" value="{{ $search }}" aria-describedby="q-hint">
            <span id="q-hint" class="hint">Nazwa, e-mail lub NIP</span>
        </div>
        <button type="submit" class="btn secondary">Szukaj</button>
    </form>

    @if ($customers->isEmpty())
        <p class="muted">Brak klientów{{ $search ? ' spełniających kryteria' : '' }}.</p>
    @else
        <div class="table-wrap">
            <table>
                <caption class="sr-only">Klienci — {{ $customers->total() }} pozycji</caption>
                <thead><tr><th scope="col">Nazwa</th><th scope="col">E-mail</th><th scope="col">NIP</th><th scope="col">Licencje</th></tr></thead>
                <tbody>
                    @foreach ($customers as $customer)
                        <tr>
                            <td><a href="{{ route('customers.show', $customer) }}">{{ $customer->name }}</a></td>
                            <td>{{ $customer->email ?? '—' }}</td>
                            <td>{{ $customer->nip ?? '—' }}</td>
                            <td>{{ $customer->licenses_count }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $customers->links() }}</div>
    @endif
@endsection
