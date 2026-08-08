<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Passport\Client as PassportClient;

class Client extends PassportClient
{
    /**
     * Pesquisa auto-provisions a user per OAuth client (see
     * AutoProvisionMcpClientUser) instead of asking a human to log in, so
     * there's no separate consent step to show either.
     */
    public function skipsAuthorization(Authenticatable $user, array $scopes): bool
    {
        return true;
    }
}
