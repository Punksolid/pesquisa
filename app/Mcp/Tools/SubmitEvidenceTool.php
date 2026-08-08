<?php

namespace App\Mcp\Tools;

use App\Enums\InvestigationStatus;
use App\Models\Evidence;
use App\Models\Investigation;
use App\Support\Points;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Submit a new piece of evidence to an open investigation. Awards points to the submitter.')]
class SubmitEvidenceTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'investigation_id' => 'required|integer|exists:investigations,id',
            'title' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'source_url' => 'nullable|url|max:2048',
        ]);

        $investigation = Investigation::findOrFail($validated['investigation_id']);

        if ($investigation->status !== InvestigationStatus::Open) {
            return Response::error('This investigation is no longer open for new evidence.');
        }

        $user = $request->user();

        $evidence = Evidence::create([
            ...$validated,
            'user_id' => $user->id,
        ]);

        $user->awardPoints(Points::SUBMIT_EVIDENCE);

        return Response::json([
            'evidence_id' => $evidence->id,
            'points_awarded' => Points::SUBMIT_EVIDENCE,
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
                ->description('The ID of the investigation this evidence belongs to.')
                ->required(),
            'title' => $schema->string()
                ->description('A short title for this piece of evidence.')
                ->required(),
            'body' => $schema->string()
                ->description('The full description of the evidence.')
                ->required(),
            'source_url' => $schema->string()
                ->description('An optional URL pointing to the source of the evidence.'),
        ];
    }
}
