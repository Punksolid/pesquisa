<?php

namespace App\Mcp\Resources;

use App\Enums\InvestigationStatus;
use App\Models\Investigation;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\MimeType;
use Laravel\Mcp\Server\Attributes\Uri;
use Laravel\Mcp\Server\Resource;

#[Description('A live snapshot of every open investigation on the Pesquisa board, as Markdown.')]
#[Uri('investigations://open')]
#[MimeType('text/markdown')]
class OpenInvestigationsResource extends Resource
{
    /**
     * Handle the resource request.
     */
    public function handle(Request $request): Response
    {
        $investigations = Investigation::query()
            ->where('status', InvestigationStatus::Open)
            ->withCount(['evidence', 'hypotheses'])
            ->latest()
            ->get();

        if ($investigations->isEmpty()) {
            return Response::text("# Open Investigations\n\nThere are no open investigations right now.");
        }

        $lines = ["# Open Investigations\n"];

        foreach ($investigations as $investigation) {
            $lines[] = sprintf(
                "## [#%d] %s\n%s\n\n_%d evidence · %d hypotheses_\n",
                $investigation->id,
                $investigation->title,
                $investigation->summary,
                $investigation->evidence_count,
                $investigation->hypotheses_count,
            );
        }

        return Response::text(implode("\n", $lines));
    }
}
