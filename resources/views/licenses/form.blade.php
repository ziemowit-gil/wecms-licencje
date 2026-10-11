@extends('layout')
@section('title', $renewing ? 'Nowy klucz' : 'Wystaw licencję')
@section('content')
    @if ($renewing)
        <h1>Nowy klucz dla {{ $renewing->domain }}</h1>
        <p>Przedłużenie, zmiana edycji lub domeny. Obecny klucz zostanie oznaczony jako zastąpiony — klient musi wkleić nowy do <code>.env</code>.</p>
    @else
        <h1>Wystaw licencję</h1>
    @endif

    <form method="post" action="{{ $renewing ? route('licenses.renew', $renewing) : route('licenses.store') }}" class="card" novalidate>
        @csrf
        @if ($renewing)
            <p><strong>Klient:</strong> {{ $renewing->customer->name }}</p>
        @elseif ($customers->isEmpty())
            <div class="alert warn" role="status">Najpierw <a href="{{ route('customers.create') }}">dodaj klienta</a>.</div>
        @else
            @include('partials.field', ['name' => 'customer_id', 'label' => 'Klient', 'type' => 'select', 'required' => true,
                'options' => $customers, 'value' => $selectedCustomer, 'placeholder' => '— wybierz —'])
        @endif

        @include('partials.field', ['name' => 'domain', 'label' => 'Domena', 'required' => true, 'value' => $license->domain,
            'hint' => 'Adres instalacji, np. klient.pl (www. i https:// zostaną pominięte). Gwiazdka * = dowolna domena (demo, testy).'])
        @include('partials.field', ['name' => 'edition', 'label' => 'Edycja', 'type' => 'select', 'required' => true,
            'options' => config('licensing.editions'), 'value' => $license->edition])
        @include('partials.field', ['name' => 'expires_on', 'label' => 'Ważna do', 'type' => 'date',
            'value' => $license->expires_on?->toDateString(), 'hint' => 'Puste = bezterminowo.'])
        @include('partials.field', ['name' => 'note', 'label' => 'Notatka w kluczu', 'value' => $license->note,
            'hint' => 'Np. numer umowy. Trafia do klucza, więc klient może ją odczytać.'])

        <div class="actions">
            <button type="submit" class="btn">{{ $renewing ? 'Wystaw nowy klucz' : 'Wystaw klucz' }}</button>
            <a href="{{ $renewing ? route('licenses.show', $renewing) : route('licenses.index') }}">Anuluj</a>
        </div>
    </form>
@endsection
