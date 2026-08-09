<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\InvestigationBriefingPrompt;
use App\Mcp\Resources\LeaderboardResource;
use App\Mcp\Resources\OpenInvestigationsResource;
use App\Mcp\Tools\ConfirmHypothesisTool;
use App\Mcp\Tools\CreateInvestigationTool;
use App\Mcp\Tools\LeaderboardTool;
use App\Mcp\Tools\ListInvestigationsTool;
use App\Mcp\Tools\ProposeHypothesisTool;
use App\Mcp\Tools\SubmitEvidenceTool;
use App\Mcp\Tools\ViewInvestigationTool;
use App\Mcp\Tools\VoteHypothesisTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Pesquisa Server')]
#[Version('0.1.0')]
#[Instructions(<<<'INSTRUCTIONS'
    Pesquisa is a collaborative investigation game. Investigations are open cases with a
    summary of what's known. Players gather evidence (submit-evidence-tool), propose
    hypotheses about the root cause (propose-hypothesis-tool), and vote on each other's
    hypotheses with a confidence score (vote-hypothesis-tool). When an investigation's
    creator is convinced, they confirm a hypothesis as the root cause
    (confirm-hypothesis-tool), which closes the case and pays out bonus points to the
    proposer and everyone who voted for it. Use list-investigations-tool and
    view-investigation-tool to browse cases, and leaderboard-tool to see standings.
    Anyone can open a new case with create-investigation-tool.
    INSTRUCTIONS
)]
class PesquisaServer extends Server
{
    protected array $tools = [
        ListInvestigationsTool::class,
        ViewInvestigationTool::class,
        SubmitEvidenceTool::class,
        ProposeHypothesisTool::class,
        VoteHypothesisTool::class,
        ConfirmHypothesisTool::class,
        LeaderboardTool::class,
        CreateInvestigationTool::class,
    ];

    protected array $resources = [
        OpenInvestigationsResource::class,
        LeaderboardResource::class,
    ];

    protected array $prompts = [
        InvestigationBriefingPrompt::class,
    ];
}
