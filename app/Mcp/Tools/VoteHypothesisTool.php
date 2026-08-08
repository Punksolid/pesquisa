<?php

namespace App\Mcp\Tools;

use App\Enums\HypothesisStatus;
use App\Models\Hypothesis;
use App\Models\HypothesisVote;
use App\Support\Points;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Vote on a proposed hypothesis, giving it a confidence score from 1 (unlikely) to 5 (very likely). Voting again updates your previous vote.')]
class VoteHypothesisTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'hypothesis_id' => 'required|integer|exists:hypotheses,id',
            'confidence' => 'required|integer|min:1|max:5',
        ]);

        $hypothesis = Hypothesis::findOrFail($validated['hypothesis_id']);

        if ($hypothesis->status !== HypothesisStatus::Proposed) {
            return Response::error('This hypothesis is no longer open for voting.');
        }

        $user = $request->user();

        $vote = HypothesisVote::updateOrCreate(
            ['hypothesis_id' => $hypothesis->id, 'user_id' => $user->id],
            ['confidence' => $validated['confidence']],
        );

        if ($vote->wasRecentlyCreated) {
            $user->awardPoints(Points::CAST_VOTE);
        }

        return Response::json([
            'vote_id' => $vote->id,
            'confidence' => $vote->confidence,
            'points_awarded' => $vote->wasRecentlyCreated ? Points::CAST_VOTE : 0,
            'your_total_points' => $user->fresh()->points,
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'hypothesis_id' => $schema->integer()
                ->description('The ID of the hypothesis to vote on.')
                ->required(),
            'confidence' => $schema->integer()
                ->description('Your confidence that this hypothesis is correct, from 1 (unlikely) to 5 (very likely).')
                ->min(1)
                ->max(5)
                ->required(),
        ];
    }
}
