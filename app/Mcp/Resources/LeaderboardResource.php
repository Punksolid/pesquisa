<?php

namespace App\Mcp\Resources;

use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Description('A live snapshot of the top 10 Pesquisa investigators ranked by points, as Markdown.')]
#[Uri('leaderboard://top')]
#[MimeType('text/markdown')]
class LeaderboardResource extends Resource
{
    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        $users = User::query()
            ->orderByDesc('points')
            ->orderBy('name')
            ->limit(10)
            ->get(['name', 'points']);

        $lines = ["# Pesquisa Leaderboard\n", '| Rank | Investigator | Points |', '|---|---|---|'];

        foreach ($users->values() as $index => $user) {
            $lines[] = sprintf('| %d | %s | %d |', $index + 1, $user->name, $user->points);
        }

        return Response::text(implode("\n", $lines));
    }
}
