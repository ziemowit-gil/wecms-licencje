@extends('layout')
@section('title', $customer->name)
@section('content')
    <div class="page-head">
        <h1>{{ $customer->name }}</h1>
        <div class="actions">
            <a class="btn secondary" href="{{ route('customers.edit', $customer) }}">Edytuj dane</a>
            <a class="btn" href="{{ route('licenses.create', ['customer' => $customer->id]) }}">Wystaw licencję</a>
        </div>
    </div>

    <div class="card">
        <dl class="details">
            <dt>Osoba kontaktowa</dt><dd>{{ $customer->contact_person ?? '—' }}</dd>
            <dt>E-mail</dt><dd>@if ($customer->email)<a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>@else — @endif</dd>
            <dt>NIP</dt><dd>{{ $customer->nip ?? '—' }}</dd>
            @if ($customer->notes)<dt>Notatki</dt><dd>{!! nl2br(e($customer->notes)) !!}</dd>@endif
        </dl>
    </div>

    <h2>Licencje</h2>
    @if ($customer->licenses->isEmpty())
        <p class="muted">Klient nie ma jeszcze licencji.</p>
        <form method="post" action="{{ route('customers.destroy', $customer) }}"
              data-confirm="Usunąć klienta {{ $customer->name }}?">
            @csrf @method('delete')
            <button type="submit" class="btn danger">Usuń klienta</button>
        </form>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th scope="col">Domena</th><th scope="col">Edycja</th><th scope="col">Ważna do</th><th scope="col">Stan</th></tr></thead>
                <tbody>
                    @foreach ($customer->licenses as $license)
                        <tr>
                            <td><a href="{{ route('licenses.show', $license) }}">{{ $license->domain }}</a> <span class="muted">#{{ $license->id }}</span></td>
                            <td>{{ $license->editionLabel() }}</td>
                            <td>{{ $license->expires_on?->format('d.m.Y') ?? 'bezterminowo' }}</td>
                            <td>@include('partials.status')</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
