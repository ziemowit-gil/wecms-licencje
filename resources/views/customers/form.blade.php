@extends('layout')
@section('title', $customer->exists ? 'Edycja klienta' : 'Nowy klient')
@section('content')
    <h1>{{ $customer->exists ? 'Edycja: '.$customer->name : 'Nowy klient' }}</h1>

    <form method="post" action="{{ $customer->exists ? route('customers.update', $customer) : route('customers.store') }}" class="card" novalidate>
        @csrf
        @if ($customer->exists) @method('put') @endif

        @include('partials.field', ['name' => 'name', 'label' => 'Nazwa organizacji', 'required' => true, 'value' => $customer->name, 'autocomplete' => 'organization'])
        @include('partials.field', ['name' => 'contact_person', 'label' => 'Osoba kontaktowa', 'value' => $customer->contact_person])
        @include('partials.field', ['name' => 'email', 'label' => 'E-mail', 'type' => 'email', 'value' => $customer->email])
        @include('partials.field', ['name' => 'nip', 'label' => 'NIP', 'value' => $customer->nip])
        @include('partials.field', ['name' => 'notes', 'label' => 'Notatki', 'type' => 'textarea', 'value' => $customer->notes])

        <div class="actions">
            <button type="submit" class="btn">Zapisz</button>
            <a href="{{ $customer->exists ? route('customers.show', $customer) : route('customers.index') }}">Anuluj</a>
        </div>
    </form>
@endsection
