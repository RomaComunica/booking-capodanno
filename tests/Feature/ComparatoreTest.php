<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Location;
use App\Models\Partner;
use App\Models\Ordine;
use App\Models\RigaOrdine;
use App\Models\TipologiaIngresso;
use App\Models\EntrataUscita;
use App\Services\StatisticsService;
use App\Filament\Pages\Comparatore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class ComparatoreTest extends TestCase
{
    use RefreshDatabase;

    private $location1;
    private $location2;
    private $partner;
    private $ingresso1;
    private $ingresso2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->partner = Partner::create([
            'idPartner' => 1,
            'nomePartner' => 'Partner Test',
            'emailPartner' => 'partner@test.com',
            'attivoPartner' => 1
        ]);

        $this->location1 = Location::create([
            'idLocation' => 1,
            'nomeLocation' => 'Location Test 1',
            'indirizzoLocation' => 'Via Test 1',
            'attivaLocation' => 1
        ]);

        $this->location2 = Location::create([
            'idLocation' => 2,
            'nomeLocation' => 'Location Test 2',
            'indirizzoLocation' => 'Via Test 2',
            'attivaLocation' => 1
        ]);

        $this->ingresso1 = TipologiaIngresso::create([
            'idLocation' => $this->location1->idLocation,
            'idIngresso' => 101,
            'nomeIngresso' => 'Ingresso Loc 1',
            'prezzoIngresso' => 50.00,
            'ingressoAttivo' => 1,
            'tipoProvvigione' => 1,
            'valoreProvvigione' => 10.00, // 10% commission
        ]);

        $this->ingresso2 = TipologiaIngresso::create([
            'idLocation' => $this->location2->idLocation,
            'idIngresso' => 102,
            'nomeIngresso' => 'Ingresso Loc 2',
            'prezzoIngresso' => 100.00,
            'ingressoAttivo' => 1,
            'tipoProvvigione' => 2,
            'valoreProvvigione' => 20.00, // Fixed 20€ commission
        ]);
    }

    /** @test */
    public function test_comparatore_page_can_be_rendered()
    {
        $this->actingAs(\App\Models\User::factory()->create());

        Livewire::test(Comparatore::class)
            ->assertStatus(200)
            ->assertHasNoErrors();
    }

    /** @test */
    public function test_statistics_service_period_stats()
    {
        $refDate = date('Y-m-d');
        $prevYearDate = date('Y-m-d', strtotime('-1 year'));

        // Order today
        $orderToday = Ordine::create([
            'numeroOrdine' => 20001,
            'idLocationOrdine' => $this->location1->idLocation,
            'idPartnerOrdine' => $this->partner->idPartner,
            'statoOrdine' => 2,
            'totaleOrdine' => 100.00,
            'dataOrdine' => $refDate . ' 12:00:00',
        ]);

        RigaOrdine::create([
            'numeroOrdine' => $orderToday->numeroOrdine,
            'idIngressoOrdine' => $this->ingresso1->idIngressoCont,
            'quantitaIngressoOrdine' => 2,
            'prezzoIngressoOrdine' => 50.00,
            'nomeIngressoOrdine' => 'Ingresso Loc 1',
            'totaleRiga' => 100.00,
        ]);

        // Order last year same day
        $orderPrevYear = Ordine::create([
            'numeroOrdine' => 20002,
            'idLocationOrdine' => $this->location2->idLocation,
            'idPartnerOrdine' => $this->partner->idPartner,
            'statoOrdine' => 2,
            'totaleOrdine' => 200.00,
            'dataOrdine' => $prevYearDate . ' 14:00:00',
        ]);

        RigaOrdine::create([
            'numeroOrdine' => $orderPrevYear->numeroOrdine,
            'idIngressoOrdine' => $this->ingresso2->idIngressoCont,
            'quantitaIngressoOrdine' => 2,
            'prezzoIngressoOrdine' => 100.00,
            'nomeIngressoOrdine' => 'Ingresso Loc 2',
            'totaleRiga' => 200.00,
        ]);

        $service = new StatisticsService();

        // Check period stats for today
        $statsToday = $service->getPeriodStats($refDate, $refDate);
        $this->assertEquals(100.00, $statsToday['revenue']);
        $this->assertEquals(1, $statsToday['orders']);
        $this->assertEquals(2, $statsToday['tickets']);

        // Check period stats for last year
        $statsPrev = $service->getPeriodStats($prevYearDate, $prevYearDate);
        $this->assertEquals(200.00, $statsPrev['revenue']);
        $this->assertEquals(1, $statsPrev['orders']);
        $this->assertEquals(2, $statsPrev['tickets']);

        // Check daily stats
        $daily = $service->getPeriodDailyStats($prevYearDate, $refDate);
        $this->assertArrayHasKey($refDate, $daily);
        $this->assertArrayHasKey($prevYearDate, $daily);
        $this->assertEquals(100.00, $daily[$refDate]['revenue']);
        $this->assertEquals(2, $daily[$refDate]['tickets']);

        // Check location breakdown
        $locStats = $service->getPeriodLocationStats($prevYearDate, $refDate);
        $this->assertCount(2, $locStats);
        $this->assertEquals(100.00, $locStats[$this->location1->idLocation]->total_revenue);
        $this->assertEquals(200.00, $locStats[$this->location2->idLocation]->total_revenue);
    }

    /** @test */
    public function test_statistics_service_compare_years_detailed_and_monthly_financials()
    {
        $year1 = 2025;
        $year2 = 2026;

        // Order in year1 (Jan 2025)
        $order1 = Ordine::create([
            'numeroOrdine' => 20003,
            'idLocationOrdine' => $this->location1->idLocation,
            'idPartnerOrdine' => $this->partner->idPartner,
            'statoOrdine' => 2,
            'totaleOrdine' => 150.00,
            'dataOrdine' => '2025-01-15 10:00:00',
        ]);
        RigaOrdine::create([
            'numeroOrdine' => $order1->numeroOrdine,
            'idIngressoOrdine' => $this->ingresso1->idIngressoCont,
            'quantitaIngressoOrdine' => 3,
            'prezzoIngressoOrdine' => 50.00,
            'nomeIngressoOrdine' => 'Ingresso Loc 1',
            'totaleRiga' => 150.00,
        ]);

        // Order in year2 (Jan 2026)
        $order2 = Ordine::create([
            'numeroOrdine' => 20004,
            'idLocationOrdine' => $this->location2->idLocation,
            'idPartnerOrdine' => $this->partner->idPartner,
            'statoOrdine' => 2,
            'totaleOrdine' => 300.00,
            'dataOrdine' => '2026-01-20 11:00:00',
        ]);
        RigaOrdine::create([
            'numeroOrdine' => $order2->numeroOrdine,
            'idIngressoOrdine' => $this->ingresso2->idIngressoCont,
            'quantitaIngressoOrdine' => 3,
            'prezzoIngressoOrdine' => 100.00,
            'nomeIngressoOrdine' => 'Ingresso Loc 2',
            'totaleRiga' => 300.00,
        ]);

        // Add Entrate and Uscite for Jan 2026
        EntrataUscita::create([
            'tipoOperazione' => 'E',
            'importo' => 50.00,
            'dataOperazione' => '2026-01-25',
            'causale' => 'Causale E',
            'descrizione' => 'Test Entrata',
        ]);
        EntrataUscita::create([
            'tipoOperazione' => 'U',
            'importo' => 20.00,
            'dataOperazione' => '2026-01-28',
            'causale' => 'Causale U',
            'descrizione' => 'Test Uscita',
        ]);

        $service = new StatisticsService();

        $comparison = $service->compareYearsDetailed($year1, $year2);
        // January is index 0
        $this->assertEquals(150.00, $comparison[0]['revenue_year1']);
        $this->assertEquals(3, $comparison[0]['tickets_year1']);
        $this->assertEquals(300.00, $comparison[0]['revenue_year2']);
        $this->assertEquals(3, $comparison[0]['tickets_year2']);

        $financials = $service->getMonthlyFinancials($year2);
        // January is index 0
        $this->assertEquals(300.00, $financials[0]['revenue']);
        // Fixed commission of 20 per ticket, 3 tickets = 60.00
        $this->assertEquals(60.00, $financials[0]['commissions']);
        // Margin = 300 - 60 = 240
        $this->assertEquals(240.00, $financials[0]['margin']);
        $this->assertEquals(50.00, $financials[0]['entrate']);
        $this->assertEquals(20.00, $financials[0]['uscite']);
        // Profit = Margin + Entrate - Uscite = 240 + 50 - 20 = 270
        $this->assertEquals(270.00, $financials[0]['profit']);
    }

    /** @test */
    public function test_comparatore_today_comparison_logic()
    {
        $this->actingAs(\App\Models\User::factory()->create());

        $refDate = '2026-06-24';
        $prevYearDate = '2025-06-24';

        // Order today
        $orderToday = Ordine::create([
            'numeroOrdine' => 30001,
            'idLocationOrdine' => $this->location1->idLocation,
            'idPartnerOrdine' => $this->partner->idPartner,
            'statoOrdine' => 2,
            'totaleOrdine' => 100.00,
            'dataOrdine' => $refDate . ' 12:00:00',
        ]);
        RigaOrdine::create([
            'numeroOrdine' => $orderToday->numeroOrdine,
            'idIngressoOrdine' => $this->ingresso1->idIngressoCont,
            'quantitaIngressoOrdine' => 2,
            'prezzoIngressoOrdine' => 50.00,
            'nomeIngressoOrdine' => 'Ingresso Loc 1',
            'totaleRiga' => 100.00,
        ]);

        // Order last year
        $orderPrevYear = Ordine::create([
            'numeroOrdine' => 30002,
            'idLocationOrdine' => $this->location2->idLocation,
            'idPartnerOrdine' => $this->partner->idPartner,
            'statoOrdine' => 2,
            'totaleOrdine' => 50.00,
            'dataOrdine' => $prevYearDate . ' 12:00:00',
        ]);
        RigaOrdine::create([
            'numeroOrdine' => $orderPrevYear->numeroOrdine,
            'idIngressoOrdine' => $this->ingresso2->idIngressoCont,
            'quantitaIngressoOrdine' => 1,
            'prezzoIngressoOrdine' => 50.00,
            'nomeIngressoOrdine' => 'Ingresso Loc 2',
            'totaleRiga' => 50.00,
        ]);

        Livewire::test(Comparatore::class)
            ->set('data.tipo_confronto', 'oggi')
            ->set('data.data_riferimento', $refDate)
            ->call('confronta')
            ->assertSet('risultati.revenue_1', 50.00)
            ->assertSet('risultati.revenue_2', 100.00)
            ->assertSet('risultati.revenue_diff', 50.00)
            ->assertSet('risultati.revenue_diff_pct', 100.0) // +100%
            ->assertSet('risultati.tickets_1', 1)
            ->assertSet('risultati.tickets_2', 2)
            ->assertSet('risultati.tickets_diff', 1)
            ->assertSet('risultati.tickets_diff_pct', 100.0)
            ->assertHasNoErrors();
    }

    /** @test */
    public function test_comparatore_custom_period_comparison_logic()
    {
        $this->actingAs(\App\Models\User::factory()->create());

        // Period 1: 2026-01-01 to 2026-01-07
        $order1 = Ordine::create([
            'numeroOrdine' => 40001,
            'idLocationOrdine' => $this->location1->idLocation,
            'idPartnerOrdine' => $this->partner->idPartner,
            'statoOrdine' => 2,
            'totaleOrdine' => 100.00,
            'dataOrdine' => '2026-01-03 12:00:00',
        ]);
        RigaOrdine::create([
            'numeroOrdine' => $order1->numeroOrdine,
            'idIngressoOrdine' => $this->ingresso1->idIngressoCont,
            'quantitaIngressoOrdine' => 2,
            'prezzoIngressoOrdine' => 50.00,
            'nomeIngressoOrdine' => 'Ingresso Loc 1',
            'totaleRiga' => 100.00,
        ]);

        // Period 2: 2026-02-01 to 2026-02-07
        $order2 = Ordine::create([
            'numeroOrdine' => 40002,
            'idLocationOrdine' => $this->location2->idLocation,
            'idPartnerOrdine' => $this->partner->idPartner,
            'statoOrdine' => 2,
            'totaleOrdine' => 300.00,
            'dataOrdine' => '2026-02-05 12:00:00',
        ]);
        RigaOrdine::create([
            'numeroOrdine' => $order2->numeroOrdine,
            'idIngressoOrdine' => $this->ingresso2->idIngressoCont,
            'quantitaIngressoOrdine' => 3,
            'prezzoIngressoOrdine' => 100.00,
            'nomeIngressoOrdine' => 'Ingresso Loc 2',
            'totaleRiga' => 300.00,
        ]);

        Livewire::test(Comparatore::class)
            ->set('data.tipo_confronto', 'periodo_personalizzato')
            ->set('data.periodo_1_da', '2026-01-01')
            ->set('data.periodo_1_a', '2026-01-07')
            ->set('data.periodo_2_da', '2026-02-01')
            ->set('data.periodo_2_a', '2026-02-07')
            ->call('confronta')
            ->assertSet('risultati.revenue_1', 100.00)
            ->assertSet('risultati.revenue_2', 300.00)
            ->assertSet('risultati.revenue_diff', 200.00)
            ->assertSet('risultati.revenue_diff_pct', 200.0) // +200%
            ->assertSet('risultati.tickets_1', 2)
            ->assertSet('risultati.tickets_2', 3)
            ->assertSet('risultati.tickets_diff', 1)
            ->assertSet('risultati.tickets_diff_pct', 50.0) // +50%
            ->assertHasNoErrors();
    }

    /** @test */
    public function test_comparatore_returns_short_labels_and_primary_comparison_flags()
    {
        $this->actingAs(\App\Models\User::factory()->create());

        // Test Today Comparison
        Livewire::test(Comparatore::class)
            ->set('data.tipo_confronto', 'oggi')
            ->set('data.data_riferimento', '2026-06-24')
            ->call('confronta')
            ->assertSet('risultati.periodo_1_short', '24/06/25')
            ->assertSet('risultati.periodo_2_short', '24/06/26')
            ->assertSet('risultati.isPrimaryComparison', true);

        // Test Year-over-Year Comparison
        Livewire::test(Comparatore::class)
            ->set('data.tipo_confronto', 'anno_contro_anno')
            ->set('data.anno_1', '2024')
            ->set('data.anno_2', '2025')
            ->call('confronta')
            ->assertSet('risultati.periodo_1_short', '2024')
            ->assertSet('risultati.periodo_2_short', '2025')
            ->assertSet('risultati.isPrimaryComparison', true)
            ->assertSet('risultati.isMonthly', false);

        // Test Custom Period Comparison
        Livewire::test(Comparatore::class)
            ->set('data.tipo_confronto', 'periodo_personalizzato')
            ->set('data.periodo_1_da', '2026-01-01')
            ->set('data.periodo_1_a', '2026-01-07')
            ->set('data.periodo_2_da', '2026-02-01')
            ->set('data.periodo_2_a', '2026-02-07')
            ->call('confronta')
            ->assertSet('risultati.periodo_1_short', '01/01/26-07/01/26')
            ->assertSet('risultati.periodo_2_short', '01/02/26-07/02/26')
            ->assertSet('risultati.isPrimaryComparison', false);
    }
}
