<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title') — Licencje weCMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
</head>
<body>
    <a class="skip-link" href="#main">Przejdź do treści</a>

    @auth
        <header class="topbar">
            <div class="inner">
                <a class="logo" href="{{ route('dashboard') }}">Licencje weCMS</a>
                <nav aria-label="Menu główne">
                    <ul>
                        @foreach ([
                            'dashboard' => ['Pulpit', 'dashboard'],
                            'licenses.index' => ['Licencje', 'licenses.*'],
                            'customers.index' => ['Klienci', 'customers.*'],
                            'settings' => ['Ustawienia', 'settings'],
                        ] as $route => [$label, $pattern])
                            <li><a href="{{ route($route) }}" @if (request()->routeIs($pattern)) aria-current="page" @endif>{{ $label }}</a></li>
                        @endforeach
                    </ul>
                </nav>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="link">Wyloguj ({{ auth()->user()->name }})</button>
                </form>
            </div>
        </header>
    @endauth

    <main id="main" tabindex="-1">
        @if (session('status'))
            <div class="alert ok" role="status">{{ session('status') }}</div>
        @endif
        @if (session('error'))
            <div class="alert error" role="alert">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert error" role="alert">
                Popraw {{ $errors->count() === 1 ? 'błąd' : 'błędy' }} w formularzu:
                <ul>
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
    <script>
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });
    </script>
    @stack('scripts')
</body>
</html>
