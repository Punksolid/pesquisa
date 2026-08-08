<?php

namespace App\Mcp\Tools;

use App\Enums\InvestigationStatus;
use App\Models\Hypothesis;
use App\Models\Investigation;
use App\Support\Points;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Propose a root-cause hypothesis for an open investigation, based on the evidence gathered so far. Awards points to the proposer.')]
class ProposeHypothesisTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'investigation_id' => 'required|integer|exists:investigations,id',
            'statement' => 'required|string|max:2000',
        ]);

        $investigation = Investigation::findOrFail($validated['investigation_id']);

        if ($investigation->status !== InvestigationStatus::Open) {
            return Response::error('This investigation is no longer open for new hypotheses.');
        }

        $user = $request->user();

        $hypothesis = Hypothesis::create([
            'investigation_id' => $investigation->id,
            'user_id' => $user->id,
            'statement' => $validated['statement'],
        ]);

        $user->awardPoints(Points::PROPOSE_HYPOTHESIS);

        return Response::json([
            'hypothesis_id' => $hypothesis->id,
            'points_awarded' => Points::PROPOSE_HYPOTHESIS,
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
            'investigation_id' => $schema->integer()
                ->description('The ID of the investigation this hypothesis addresses.')
                ->required(),
            'statement' => $schema->string()
                ->description('The hypothesis statement: what you believe the root cause to be, and why.')
                ->required(),
        ];
    }
}
