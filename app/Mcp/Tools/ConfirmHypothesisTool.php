<?php

namespace App\Mcp\Tools;

use App\Enums\HypothesisStatus;
use App\Enums\InvestigationStatus;
use App\Models\Hypothesis;
use App\Support\Points;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Confirm a hypothesis as the root cause, closing the investigation. Only the investigation creator may do this. Awards a bonus to the proposer and to everyone who voted for the winning hypothesis.')]
class ConfirmHypothesisTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'hypothesis_id' => 'required|integer|exists:hypotheses,id',
        ]);

        $hypothesis = Hypothesis::with(['investigation', 'votes.user'])->findOrFail($validated['hypothesis_id']);
        $investigation = $hypothesis->investigation;

        $user = $request->user();

        if ($investigation->created_by !== $user->id) {
            return Response::error('Only the investigation creator can confirm a hypothesis as the root cause.');
        }

        if ($investigation->status !== InvestigationStatus::Open) {
            return Response::error('This investigation is already resolved.');
        }

        DB::transaction(function () use ($hypothesis): void {
            $hypothesis->investigation()->update([
                'status' => InvestigationStatus::Solved,
                'confirmed_hypothesis_id' => $hypothesis->id,
            ]);

            $hypothesis->update(['status' => HypothesisStatus::Confirmed]);

            $hypothesis->investigation->hypotheses()
                ->whereKeyNot($hypothesis->id)
                ->update(['status' => HypothesisStatus::Rejected]);

            $hypothesis->user->awardPoints(Points::HYPOTHESIS_CONFIRMED_BONUS);

            $hypothesis->votes->each(
                fn ($vote) => $vote->user->awardPoints(Points::CORRECT_VOTER_BONUS)
            );
        });

        return Response::json([
            'investigation_id' => $investigation->id,
            'confirmed_hypothesis_id' => $hypothesis->id,
            'proposer_bonus' => Points::HYPOTHESIS_CONFIRMED_BONUS,
            'voter_bonus_each' => Points::CORRECT_VOTER_BONUS,
            'voters_rewarded' => $hypothesis->votes->count(),
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
                ->description('The ID of the hypothesis to confirm as the root cause.')
                ->required(),
        ];
    }
}
