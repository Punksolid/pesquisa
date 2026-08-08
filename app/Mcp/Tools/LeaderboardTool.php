<?php

namespace App\Mcp\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Get the Pesquisa leaderboard: the top investigators ranked by points earned from submitting evidence, proposing hypotheses, and voting correctly.')]
class LeaderboardTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $limit = $validated['limit'] ?? 10;

        $leaderboard = User::query()
            ->orderByDesc('points')
            ->orderBy('name')
            ->limit($limit)
            ->get(['id', 'name', 'points'])
            ->values()
            ->map(fn (User $user, int $index) => [
                'rank' => $index + 1,
                'name' => $user->name,
                'points' => $user->points,
            ]);

        return Response::json($leaderboard);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()
                ->description('How many top investigators to return. Defaults to 10.')
                ->min(1)
                ->max(100),
        ];
    }
}
