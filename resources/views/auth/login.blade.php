<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log in - {{ config('app.name', 'Pesquisa') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="font-sans antialiased bg-background text-foreground">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-sm rounded-lg border bg-card text-card-foreground shadow-sm p-6">
            <h1 class="text-lg font-semibold mb-4">Log in to Pesquisa</h1>

            @if ($errors->any())
                <div class="mb-4 text-sm text-red-600">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium mb-1">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                        class="w-full rounded-md border px-3 py-2 text-sm">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium mb-1">Password</label>
                    <input id="password" name="password" type="password" required
                        class="w-full rounded-md border px-3 py-2 text-sm">
                </div>

                <button type="submit"
                    class="w-full rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground">
                    Log in
                </button>
            </form>
        </div>
    </div>
</body>
</html>
