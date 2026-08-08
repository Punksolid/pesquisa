<?php

namespace Database\Seeders;

use App\Enums\HypothesisStatus;
use App\Enums\InvestigationStatus;
use App\Models\Evidence;
use App\Models\Hypothesis;
use App\Models\HypothesisVote;
use App\Models\Investigation;
use App\Models\User;
use App\Support\Points;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoInvestigationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        [$ana, $marco, $priya, $tom, $lucia] = collect([
            ['name' => 'Ana Torres', 'email' => 'ana@pesquisa.test'],
            ['name' => 'Marco Vidal', 'email' => 'marco@pesquisa.test'],
            ['name' => 'Priya Singh', 'email' => 'priya@pesquisa.test'],
            ['name' => 'Tom Alvarez', 'email' => 'tom@pesquisa.test'],
            ['name' => 'Lucia Fernandez', 'email' => 'lucia@pesquisa.test'],
        ])->map(fn (array $attrs) => User::query()->firstOrCreate(
            ['email' => $attrs['email']],
            [
                'name' => $attrs['name'],
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        ))->all();

        $this->solvedCase($ana, $marco, $priya, $tom);
        $this->openCase($lucia, $marco, $priya);
    }

    private function solvedCase(User $ana, User $marco, User $priya, User $tom): void
    {
        $investigation = Investigation::query()->firstOrCreate(
            ['slug' => 'caida-conversion-checkout-viernes'],
            [
                'title' => 'Caída del 12% en la conversión de checkout tras el deploy del viernes',
                'summary' => 'Desde el deploy de las 17:00 del viernes, la tasa de conversión en el checkout '
                    .'cayó un 12% y el volumen de tickets de soporte por pagos fallidos se triplicó. '
                    .'Necesitamos encontrar la causa raíz antes del lunes.',
                'status' => InvestigationStatus::Open,
                'created_by' => $ana->id,
            ],
        );

        $evidenceItems = [
            [
                'user' => $marco,
                'title' => 'Logs de error 504 en el gateway de pagos',
                'body' => 'A partir de las 17:03 del viernes, el rate de 504 Gateway Timeout hacia el '
                    .'proveedor de pagos subió de 0.1% a 6.4%. Coincide casi exactamente con el deploy.',
                'source_url' => 'https://status.internal.example.com/payments-gateway',
            ],
            [
                'user' => $priya,
                'title' => 'El deploy del viernes redujo el timeout del cliente HTTP de 10s a 3s',
                'body' => 'El PR #4821 cambió el timeout por defecto del cliente HTTP usado para llamar al '
                    .'gateway de pagos de 10 segundos a 3 segundos, como parte de una limpieza de configuración '
                    .'no relacionada.',
                'source_url' => null,
            ],
            [
                'user' => $tom,
                'title' => 'Los tickets de soporte mencionan "processing" infinito',
                'body' => 'De 40 tickets revisados, 34 describen que el checkout se queda cargando y luego '
                    .'muestra un error genérico, consistente con un timeout del lado del cliente.',
                'source_url' => null,
            ],
        ];

        foreach ($evidenceItems as $item) {
            $evidence = Evidence::query()->firstOrCreate(
                ['investigation_id' => $investigation->id, 'title' => $item['title']],
                [
                    'user_id' => $item['user']->id,
                    'body' => $item['body'],
                    'source_url' => $item['source_url'],
                ],
            );

            if ($evidence->wasRecentlyCreated) {
                $item['user']->awardPoints(Points::SUBMIT_EVIDENCE);
            }
        }

        $confirmedHypothesis = Hypothesis::query()->firstOrCreate(
            [
                'investigation_id' => $investigation->id,
                'statement' => 'El timeout de 3s introducido en el PR #4821 es demasiado agresivo para el '
                    .'p95 de latencia real del gateway de pagos, causando que pagos válidos se corten a mitad '
                    .'de proceso.',
            ],
            [
                'user_id' => $priya->id,
                'status' => HypothesisStatus::Proposed,
            ],
        );

        if ($confirmedHypothesis->wasRecentlyCreated) {
            $priya->awardPoints(Points::PROPOSE_HYPOTHESIS);
        }

        $redHerring = Hypothesis::query()->firstOrCreate(
            [
                'investigation_id' => $investigation->id,
                'statement' => 'El proveedor de pagos está teniendo una degradación general de servicio, '
                    .'no relacionada con nuestro deploy.',
            ],
            [
                'user_id' => $tom->id,
                'status' => HypothesisStatus::Proposed,
            ],
        );

        if ($redHerring->wasRecentlyCreated) {
            $tom->awardPoints(Points::PROPOSE_HYPOTHESIS);
        }

        $votes = [
            [$confirmedHypothesis, $marco, 5],
            [$confirmedHypothesis, $tom, 4],
            [$confirmedHypothesis, $ana, 5],
            [$redHerring, $marco, 2],
        ];

        foreach ($votes as [$hypothesis, $voter, $confidence]) {
            $vote = HypothesisVote::query()->firstOrCreate(
                ['hypothesis_id' => $hypothesis->id, 'user_id' => $voter->id],
                ['confidence' => $confidence],
            );

            if ($vote->wasRecentlyCreated) {
                $voter->awardPoints(Points::CAST_VOTE);
            }
        }

        if ($investigation->status !== InvestigationStatus::Solved) {
            $investigation->update([
                'status' => InvestigationStatus::Solved,
                'confirmed_hypothesis_id' => $confirmedHypothesis->id,
            ]);

            $confirmedHypothesis->update(['status' => HypothesisStatus::Confirmed]);
            $redHerring->update(['status' => HypothesisStatus::Rejected]);

            $priya->awardPoints(Points::HYPOTHESIS_CONFIRMED_BONUS);

            foreach ($confirmedHypothesis->votes as $vote) {
                $vote->user->awardPoints(Points::CORRECT_VOTER_BONUS);
            }
        }
    }

    private function openCase(User $lucia, User $marco, User $priya): void
    {
        $investigation = Investigation::query()->firstOrCreate(
            ['slug' => 'picos-latencia-busqueda-nocturna'],
            [
                'title' => 'Picos de latencia intermitentes en el buscador entre las 2am y 4am',
                'summary' => 'El p99 de latencia del endpoint de búsqueda sube de 300ms a más de 4s todas las '
                    .'noches entre las 2am y 4am (UTC), sin un patrón de tráfico que lo explique. Aún en '
                    .'investigación, sin causa confirmada.',
                'status' => InvestigationStatus::Open,
                'created_by' => $lucia->id,
            ],
        );

        $evidence = Evidence::query()->firstOrCreate(
            [
                'investigation_id' => $investigation->id,
                'title' => 'El job de reindexado nocturno corre en el mismo cluster',
                'body' => 'El cron de reindexado de Elasticsearch corre a las 2:00am y tarda entre 90 y 120 '
                    .'minutos, en el mismo cluster que sirve las búsquedas de producción.',
            ],
            ['user_id' => $marco->id, 'source_url' => null],
        );

        if ($evidence->wasRecentlyCreated) {
            $marco->awardPoints(Points::SUBMIT_EVIDENCE);
        }

        $hypothesis = Hypothesis::query()->firstOrCreate(
            [
                'investigation_id' => $investigation->id,
                'statement' => 'El job de reindexado nocturno satura I/O en el cluster compartido de '
                    .'Elasticsearch, degradando la latencia de búsquedas en vivo.',
            ],
            [
                'user_id' => $priya->id,
                'status' => HypothesisStatus::Proposed,
            ],
        );

        if ($hypothesis->wasRecentlyCreated) {
            $priya->awardPoints(Points::PROPOSE_HYPOTHESIS);
        }

        $vote = HypothesisVote::query()->firstOrCreate(
            ['hypothesis_id' => $hypothesis->id, 'user_id' => $lucia->id],
            ['confidence' => 4],
        );

        if ($vote->wasRecentlyCreated) {
            $lucia->awardPoints(Points::CAST_VOTE);
        }
    }
}
