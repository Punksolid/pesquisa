<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Passport\ClientRepository;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lets an MCP client connect without any human ever filling out a login
 * form: the client_id it sent when registering (RFC 7591) is used as a
 * stable identity. The first time a given client_id shows up at
 * /oauth/authorize, a matching User is created and logged in on the spot.
 *
 * Combined with App\Models\Client::skipsAuthorization() (always true), this
 * means connecting an MCP client is the entire "sign-up" flow — no consent
 * screen either.
 *
 * Trade-off: identity is tied to "this client installation", not to a
 * person. There's no password to prove it's the same human next time, and
 * a client that re-registers on every launch gets a fresh account each
 * time. An already-authenticated session (e.g. someone who logged in
 * manually via /login) is left untouched.
 */
class AutoProvisionMcpClientUser
{
    public function __construct(private readonly ClientRepository $clients) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('web')->check()) {
            return $next($request);
        }

        $clientId = $request->query('client_id');

        if (! is_string($clientId) || $clientId === '') {
            return $next($request);
        }

        $oauthClient = $this->clients->findActive($clientId);

        if ($oauthClient === null) {
            return $next($request);
        }

        $user = User::firstOrCreate(
            ['passport_client_id' => $clientId],
            [
                'name' => $oauthClient->name ?: 'Investigador MCP',
                'email' => "mcp-client-{$clientId}@pesquisa.local",
                'password' => Hash::make(Str::random(40)),
                'email_verified_at' => now(),
            ],
        );

        Auth::guard('web')->login($user);

        return $next($request);
    }
}
