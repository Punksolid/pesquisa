<?php

namespace App\Mcp\Prompts;

use App\Models\Investigation;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Description('Brief an AI investigator on a case: summarizes the evidence and existing hypotheses, then asks it to reason about the most likely root cause.')]
class InvestigationBriefingPrompt extends Prompt
{
    /**
     * Handle the prompt request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'investigation_id' => 'required|integer|exists:investigations,id',
        ]);

        $investigation = Investigation::with(['evidence', 'hypotheses.votes'])
            ->findOrFail($validated['investigation_id']);

        $evidence = $investigation->evidence
            ->map(fn ($item) => "- **{$item->title}**: {$item->body}")
            ->implode("\n");

        $hypotheses = $investigation->hypotheses
            ->map(fn ($h) => "- \"{$h->statement}\" (status: {$h->status->value}, {$h->votes->count()} votes)")
            ->implode("\n");

        $prompt = <<<PROMPT
            You are investigating the following case on Pesquisa.

            # {$investigation->title}

            {$investigation->summary}

            ## Evidence gathered so far
            {$evidence}

            ## Hypotheses proposed so far
            {$hypotheses}

            Review the evidence above. Identify gaps in the evidence, evaluate the existing
            hypotheses, and propose the most likely root cause with your reasoning. If you
            believe a new hypothesis is warranted, use the propose-hypothesis-tool to submit it.
            PROMPT;

        return Response::text($prompt);
    }

    /**
     * Get the prompt's arguments.
     *
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                name: 'investigation_id',
                description: 'The ID of the investigation to be briefed on.',
                required: true,
            ),
        ];
    }
}
