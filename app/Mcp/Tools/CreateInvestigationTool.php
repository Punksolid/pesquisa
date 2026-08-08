<?php

namespace App\Mcp\Tools;

use App\Enums\InvestigationStatus;
use App\Models\Investigation;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Open a new investigation on the Pesquisa board. Restricted to the Pesquisa admin.')]
class CreateInvestigationTool extends Tool
{
    /**
     * Only the admin sees (and can call) this tool: it's filtered out of
     * both tools/list and tools/call for everyone else, since CallTool
     * resolves tools through the same eligibleForRegistration() check.
     */
    public function shouldRegister(Request $request): bool
    {
        return $request->user()?->email === config('pesquisa.admin_email');
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        if ($request->user()?->email !== config('pesquisa.admin_email')) {
            return Response::error('Solo el admin de Pesquisa puede crear investigaciones.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'summary' => 'required|string|max:5000',
        ]);

        $investigation = Investigation::create([
            'title' => $validated['title'],
            'summary' => $validated['summary'],
            'status' => InvestigationStatus::Open,
            'created_by' => $request->user()->id,
        ]);

        return Response::json([
            'investigation_id' => $investigation->id,
            'slug' => $investigation->slug,
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
            'title' => $schema->string()
                ->description('El título del nuevo caso.')
                ->required(),
            'summary' => $schema->string()
                ->description('Qué se sabe hasta ahora sobre el caso.')
                ->required(),
        ];
    }
}
