<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar - {{ config('app.name', 'Pesquisa') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased bg-background text-foreground">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-sm rounded-lg border bg-card text-card-foreground shadow-sm p-6">
            <h1 class="text-lg font-semibold mb-1">Entrar a Pesquisa</h1>
            <p class="text-sm text-muted-foreground mb-4">
                ¿Primera vez? Completá tu nombre y elegí una contraseña: tu cuenta se crea al instante.
                ¿Ya tenés cuenta? Dejá el nombre en blanco e ingresá tu contraseña.
            </p>

            @if ($errors->any())
                <div class="mb-4 text-sm text-red-600">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-medium mb-1">Nombre (solo para cuenta nueva)</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" autofocus
                        class="w-full rounded-md border px-3 py-2 text-sm">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium mb-1">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required
                        class="w-full rounded-md border px-3 py-2 text-sm">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium mb-1">Contraseña</label>
                    <input id="password" name="password" type="password" required minlength="8"
                        class="w-full rounded-md border px-3 py-2 text-sm">
                </div>

                <button type="submit"
                    class="w-full rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground">
                    Continuar
                </button>
            </form>
        </div>
    </div>
</body>
</html>
