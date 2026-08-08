<?php

namespace App\Mcp\Tools;

use App\Enums\InvestigationStatus;
use App\Models\Investigation;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('List investigations on the Pesquisa board, optionally filtered by status (open, solved, archived).')]
class ListInvestigationsTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'status' => 'nullable|string|in:open,solved,archived',
        ]);

        $query = Investigation::query()->withCount(['evidence', 'hypotheses'])->latest();

        if (! empty($validated['status'])) {
            $query->where('status', InvestigationStatus::from($validated['status']));
        }

        $investigations = $query->get()->map(fn (Investigation $investigation) => [
            'id' => $investigation->id,
            'title' => $investigation->title,
            'slug' => $investigation->slug,
            'status' => $investigation->status->value,
            'evidence_count' => $investigation->evidence_count,
            'hypotheses_count' => $investigation->hypotheses_count,
        ]);

        if ($investigations->isEmpty()) {
            return Response::text('No investigations found.');
        }

        return Response::json($investigations);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()
                ->enum(['open', 'solved', 'archived'])
                ->description('Filter investigations by status. Omit to list all.'),
        ];
    }
}
