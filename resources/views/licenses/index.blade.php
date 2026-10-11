@extends('layout')
@section('title', 'Licencje')
@section('content')
    <div class="page-head">
        <h1>Licencje</h1>
        <a class="btn" href="{{ route('licenses.create') }}">Wystaw licencję</a>
    </div>

    <form method="get" class="filters" role="search" aria-label="Filtruj licencje">
        <div class="field">
            <label for="q">Szukaj</label>
            <input id="q" type="search" name="q" value="{{ $search }}" aria-describedby="q-hint">
            <span id="q-hint" class="hint">Domena lub nazwa klienta</span>
        </div>
        <div class="field">
            <label for="status">Stan</label>
            <select id="status" name="status">
                <option value="">Wszystkie</option>
                @foreach (\App\Models\License::STATUSES as $key => $label)
                    <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn secondary">Filtruj</button>
    </form>

    @if ($licenses->isEmpty())
        <p class="muted">Brak licencji{{ $search || $status ? ' spełniających kryteria' : '' }}.</p>
    @else
        <div class="table-wrap">
            <table>
                <caption class="sr-only">Licencje — {{ $licenses->total() }} pozycji</caption>
                <thead><tr><th scope="col">Domena</th><th scope="col">Klient</th><th scope="col">Edycja</th><th scope="col">Ważna do</th><th scope="col">Stan</th><th scope="col">Ostatnie sprawdzenie</th></tr></thead>
                <tbody>
                    @foreach ($licenses as $license)
                        <tr>
                            <td><a href="{{ route('licenses.show', $license) }}">{{ $license->domain }}</a></td>
                            <td><a href="{{ route('customers.show', $license->customer) }}">{{ $license->customer->name }}</a></td>
                            <td>{{ $license->editionLabel() }}</td>
                            <td>{{ $license->expires_on?->format('d.m.Y') ?? 'bezterminowo' }}</td>
                            <td>@include('partials.status')</td>
                            <td>{{ $license->last_check_at?->format('d.m.Y H:i') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pagination">{{ $licenses->links() }}</div>
    @endif
@endsection
