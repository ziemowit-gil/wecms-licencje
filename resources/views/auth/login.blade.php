@extends('layout')
@section('title', 'Logowanie')
@section('content')
    <div class="login card">
        <h1>Licencje weCMS</h1>
        <p class="muted">Zaloguj się, aby zarządzać kluczami licencyjnymi.</p>
        <form method="post" action="{{ route('login.attempt') }}" novalidate>
            @csrf
            @include('partials.field', ['name' => 'email', 'label' => 'E-mail', 'type' => 'email', 'required' => true, 'autocomplete' => 'username'])
            @include('partials.field', ['name' => 'password', 'label' => 'Hasło', 'type' => 'password', 'required' => true, 'autocomplete' => 'current-password', 'value' => ''])
            <button type="submit" class="btn">Zaloguj się</button>
        </form>
    </div>
@endsection
