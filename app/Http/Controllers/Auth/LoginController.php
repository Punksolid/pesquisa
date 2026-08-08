<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Log in an existing account, or create one on the spot if the email is new.
     *
     * This is the only entry point into Pesquisa: an MCP client's OAuth
     * consent flow redirects unauthenticated users here, so simply
     * connecting and filling this form out once is enough to register.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if ($user) {
            if (! Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']], $request->boolean('remember'))) {
                return back()->withErrors([
                    'password' => 'Esa contraseña no coincide con la cuenta existente.',
                ])->onlyInput('email', 'name');
            }
        } else {
            $user = User::create([
                'name' => ($validated['name'] ?? null) ?: Str::before($validated['email'], '@'),
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),
            ]);

            Auth::login($user, $request->boolean('remember'));
        }

        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
