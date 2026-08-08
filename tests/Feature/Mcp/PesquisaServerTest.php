<?php

namespace Tests\Feature\Mcp;

use App\Enums\HypothesisStatus;
use App\Enums\InvestigationStatus;
use App\Mcp\Servers\PesquisaServer;
use App\Mcp\Tools\ConfirmHypothesisTool;
use App\Mcp\Tools\CreateInvestigationTool;
use App\Mcp\Tools\LeaderboardTool;
use App\Mcp\Tools\ListInvestigationsTool;
use App\Mcp\Tools\ProposeHypothesisTool;
use App\Mcp\Tools\SubmitEvidenceTool;
use App\Mcp\Tools\ViewInvestigationTool;
use App\Mcp\Tools\VoteHypothesisTool;
use App\Models\Hypothesis;
use App\Models\Investigation;
use App\Models\User;
use App\Support\Points;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PesquisaServerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_all_eight_tools_two_resources_and_one_prompt(): void
    {
        $defaults = (new \ReflectionClass(PesquisaServer::class))->getDefaultProperties();

        $this->assertCount(8, $defaults['tools']);
        $this->assertCount(2, $defaults['resources']);
        $this->assertCount(1, $defaults['prompts']);
    }

    public function test_guests_cannot_call_tools_that_require_a_user(): void
    {
        $investigation = Investigation::factory()->create();

        PesquisaServer::tool(SubmitEvidenceTool::class, [
            'investigation_id' => $investigation->id,
            'title' => 'Some evidence',
            'body' => 'Some body',
        ])->assertHasErrors();
    }

    public function test_submitting_evidence_awards_points_and_persists_it(): void
    {
        $user = User::factory()->create(['points' => 0]);
        $investigation = Investigation::factory()->create(['status' => InvestigationStatus::Open]);

        PesquisaServer::actingAs($user)
            ->tool(SubmitEvidenceTool::class, [
                'investigation_id' => $investigation->id,
                'title' => 'Suspicious log line',
                'body' => 'Found a 500 error right before the outage.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('evidence', [
            'investigation_id' => $investigation->id,
            'user_id' => $user->id,
            'title' => 'Suspicious log line',
        ]);

        $this->assertSame(Points::SUBMIT_EVIDENCE, $user->fresh()->points);
    }

    public function test_proposing_a_hypothesis_awards_points(): void
    {
        $user = User::factory()->create(['points' => 0]);
        $investigation = Investigation::factory()->create(['status' => InvestigationStatus::Open]);

        PesquisaServer::actingAs($user)
            ->tool(ProposeHypothesisTool::class, [
                'investigation_id' => $investigation->id,
                'statement' => 'A misconfigured cache TTL caused stale reads.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('hypotheses', [
            'investigation_id' => $investigation->id,
            'user_id' => $user->id,
        ]);

        $this->assertSame(Points::PROPOSE_HYPOTHESIS, $user->fresh()->points);
    }

    public function test_voting_twice_updates_the_vote_instead_of_double_awarding_points(): void
    {
        $voter = User::factory()->create(['points' => 0]);
        $hypothesis = Hypothesis::factory()->create(['status' => HypothesisStatus::Proposed]);

        PesquisaServer::actingAs($voter)
            ->tool(VoteHypothesisTool::class, ['hypothesis_id' => $hypothesis->id, 'confidence' => 3])
            ->assertOk();

        PesquisaServer::actingAs($voter)
            ->tool(VoteHypothesisTool::class, ['hypothesis_id' => $hypothesis->id, 'confidence' => 5])
            ->assertOk();

        $this->assertSame(1, $hypothesis->votes()->count());
        $this->assertSame(5, $hypothesis->votes()->first()->confidence);
        $this->assertSame(Points::CAST_VOTE, $voter->fresh()->points);
    }

    public function test_only_the_investigation_creator_can_confirm_a_hypothesis(): void
    {
        $creator = User::factory()->create();
        $proposer = User::factory()->create();
        $outsider = User::factory()->create();

        $investigation = Investigation::factory()->create([
            'created_by' => $creator->id,
            'status' => InvestigationStatus::Open,
        ]);

        $hypothesis = Hypothesis::factory()->create([
            'investigation_id' => $investigation->id,
            'user_id' => $proposer->id,
            'status' => HypothesisStatus::Proposed,
        ]);

        PesquisaServer::actingAs($outsider)
            ->tool(ConfirmHypothesisTool::class, ['hypothesis_id' => $hypothesis->id])
            ->assertHasErrors();

        $this->assertSame(InvestigationStatus::Open, $investigation->fresh()->status);
    }

    public function test_confirming_a_hypothesis_solves_the_investigation_and_pays_out_bonuses(): void
    {
        $creator = User::factory()->create();
        $proposer = User::factory()->create(['points' => 0]);
        $voter = User::factory()->create(['points' => 0]);

        $investigation = Investigation::factory()->create([
            'created_by' => $creator->id,
            'status' => InvestigationStatus::Open,
        ]);

        $winner = Hypothesis::factory()->create([
            'investigation_id' => $investigation->id,
            'user_id' => $proposer->id,
            'status' => HypothesisStatus::Proposed,
        ]);

        $loser = Hypothesis::factory()->create([
            'investigation_id' => $investigation->id,
            'status' => HypothesisStatus::Proposed,
        ]);

        $winner->votes()->create(['user_id' => $voter->id, 'confidence' => 5]);

        PesquisaServer::actingAs($creator)
            ->tool(ConfirmHypothesisTool::class, ['hypothesis_id' => $winner->id])
            ->assertOk();

        $investigation->refresh();
        $this->assertSame(InvestigationStatus::Solved, $investigation->status);
        $this->assertSame($winner->id, $investigation->confirmed_hypothesis_id);
        $this->assertSame(HypothesisStatus::Confirmed, $winner->fresh()->status);
        $this->assertSame(HypothesisStatus::Rejected, $loser->fresh()->status);
        $this->assertSame(Points::HYPOTHESIS_CONFIRMED_BONUS, $proposer->fresh()->points);
        $this->assertSame(Points::CORRECT_VOTER_BONUS, $voter->fresh()->points);
    }

    public function test_view_investigation_tool_returns_the_full_dossier(): void
    {
        $user = User::factory()->create();
        $investigation = Investigation::factory()->create();

        PesquisaServer::actingAs($user)
            ->tool(ViewInvestigationTool::class, ['investigation_id' => $investigation->id])
            ->assertOk()
            ->assertSee($investigation->title);
    }

    public function test_list_investigations_tool_can_filter_by_status(): void
    {
        $user = User::factory()->create();
        Investigation::factory()->create(['status' => InvestigationStatus::Open]);
        Investigation::factory()->create(['status' => InvestigationStatus::Solved]);

        PesquisaServer::actingAs($user)
            ->tool(ListInvestigationsTool::class, ['status' => 'solved'])
            ->assertOk();
    }

    public function test_create_investigation_tool_is_unreachable_for_a_regular_user(): void
    {
        $user = User::factory()->create(['email' => 'nobody@pesquisa.test']);

        PesquisaServer::actingAs($user)
            ->tool(CreateInvestigationTool::class, [
                'title' => 'Intento no autorizado',
                'summary' => 'Esto no debería crearse.',
            ])
            ->assertHasErrors(['not found']);

        $this->assertDatabaseMissing('investigations', ['title' => 'Intento no autorizado']);
    }

    public function test_create_investigation_tool_works_for_the_admin(): void
    {
        $admin = User::factory()->create(['email' => config('pesquisa.admin_email')]);

        PesquisaServer::actingAs($admin)
            ->tool(CreateInvestigationTool::class, [
                'title' => 'Caso nuevo del admin',
                'summary' => 'Un caso recién abierto.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('investigations', [
            'title' => 'Caso nuevo del admin',
            'created_by' => $admin->id,
            'status' => InvestigationStatus::Open,
        ]);
    }

    public function test_leaderboard_tool_orders_users_by_points_descending(): void
    {
        $user = User::factory()->create();
        User::factory()->create(['name' => 'Low Scorer', 'points' => 5]);
        User::factory()->create(['name' => 'High Scorer', 'points' => 50]);

        PesquisaServer::actingAs($user)
            ->tool(LeaderboardTool::class, ['limit' => 5])
            ->assertOk()
            ->assertSee('High Scorer');
    }
}
