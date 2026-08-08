<?php

namespace App\Mcp\Tools;

use App\Models\Investigation;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('View the full dossier for one investigation: its summary, all submitted evidence, and all proposed hypotheses with their vote tallies.')]
class ViewInvestigationTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'investigation_id' => 'required|integer|exists:investigations,id',
        ]);

        $investigation = Investigation::with([
            'creator:id,name',
            'evidence.user:id,name',
            'hypotheses.user:id,name',
            'hypotheses.votes',
        ])->findOrFail($validated['investigation_id']);

        return Response::json([
            'id' => $investigation->id,
            'title' => $investigation->title,
            'status' => $investigation->status->value,
            'summary' => $investigation->summary,
            'created_by' => $investigation->creator->name,
            'confirmed_hypothesis_id' => $investigation->confirmed_hypothesis_id,
            'evidence' => $investigation->evidence->map(fn ($item) => [
                'id' => $item->id,
                'title' => $item->title,
                'body' => $item->body,
                'source_url' => $item->source_url,
                'submitted_by' => $item->user->name,
            ]),
            'hypotheses' => $investigation->hypotheses->map(fn ($hypothesis) => [
                'id' => $hypothesis->id,
                'statement' => $hypothesis->statement,
                'status' => $hypothesis->status->value,
                'proposed_by' => $hypothesis->user->name,
                'votes' => $hypothesis->votes->count(),
                'average_confidence' => round((float) $hypothesis->votes->avg('confidence'), 2),
            ]),
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
                ->description('The ID of the investigation to view.')
                ->required(),
        ];
    }
}
